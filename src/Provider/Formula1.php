<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    2015-03-15
 */
namespace Provider;


use Beryllium\Cache\Client\ClientInterface;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Formula1
 * @package Provider
 */
class Formula1 extends BaseProvider
{
    const HOST_URL = "http://www.formula1.com";

    const RESULTS_BASE_URL = "/content/fom-website/en/championship/results/";

    const RACES_URL = "/content/fom-website/en/championship/races/2015.html";

    const RACE_RESULTS_URL = "2015-race-results.html";

    const QUALIFYING_SITE = '/qualifying.html';

    const RACE_SITE = '/race.html';

    const DRIVERS_URL = "/content/fom-website/en/championship/drivers.html";

    const CACHE_KEY_GRAND_PRIX_RESULT_URLS = "GRAND_PRIX_RESULT_URLS";
    const CACHE_KEY_GRAND_PRIX = "GRAND_PRIX";
    const CACHE_KEY_DRIVERS_DATA = "DRIVERS_DATA";
    const CACHE_KEY_QUALIFYING_RESULTS = "QUALIFYING_RESULTS_";
    const CACHE_KEY_RACE_RESULTS = "RACE_RESULTS_";

    /** @var \Beryllium\Cache\Cache  */
    protected $cacheClient;

    /**
     * Contains all the available Grand Prix result URLs
     *
     * @var array
     */
    protected $races;

    /**
     * Contains grand prix date, title, photos. Does'nt include result data.
     *
     * @var array
     */
    protected $racesInfo;

    /** @var  array */
    protected $drivers;

    /** @var  array */
    protected $teams;

    /** @var  array */
    protected $engines;

    /**
     * @param \Beryllium\Cache\Cache $cacheClient
     */
    public function __construct(\Beryllium\Cache\Cache $cacheClient)
    {
        $this->cacheClient = $cacheClient;

        $this->fetchGrandPrixResultURLs();
        $this->fetchDriversData();
    }

    /**
     * @return array
     */
    public function getDriversData()
    {
        $cacheResults = $this->cacheClient->get(self::CACHE_KEY_DRIVERS_DATA);

        if (false === $cacheResults) {
            $this->fetchDriversData();
        } else {
            $this->drivers = $cacheResults;
        }

        return $this->drivers;
    }

    /**
     * @return array
     */
    public function getTeams()
    {
        $this->fetchTeams();

        return $this->teams;
    }

    /**
     * @return array
     */
    public function getEngines()
    {
        $this->fetchEngines();

        return $this->engines;
    }

    /**
     * @param $raceName
     *
     * @return array|bool
     */
    public function getRaceByName($raceName)
    {
        $raceHash = $this->hash($raceName);

        return !empty($this->races[$raceHash])
            ? $this->races[$raceHash]
            : false;
    }

    /**
     * @return array
     */
    public function getGrandPrixResultUrls()
    {
        $cacheResults = $this->cacheClient->get(self::CACHE_KEY_GRAND_PRIX_RESULT_URLS);

        if (false === $cacheResults) {
            $this->fetchGrandPrixResultURLs();
        } else {
            $this->races = $cacheResults;
        }

        return $this->races;
    }

    /**
     * @return array|mixed
     */
    public function getGrandPrix()
    {
        $cacheResults = $this->cacheClient->get(self::CACHE_KEY_GRAND_PRIX);

//        if (false === $cacheResults) {
            $this->fetchGrandPrix();
//        } else {
//            $this->racesInfo = $cacheResults;
//        }

        return $this->racesInfo;
    }

    /*
     * Fetchers
     */

    /**
     * @return array
     */
    public function fetchGrandPrixResultURLs()
    {
        $crawler = $this->getData($this->getBaseResultsUrl() . self::RACE_RESULTS_URL);

        $grandPrix = $crawler->filterXPath('//div[@class="group article-columns"]');

        $this->races = [];

        $grandPrix->each(function (Crawler $race, $key) {
            $title = $race->filterXPath('//h4')->text();

            $this->addRaceInfo([
                'url'   => $race->filterXPath('//a/@href')->text(),
                'title' => $title,
                'slug'  => strtolower(str_replace(' ', '-', $title)),
                'photo' => $race->filterXPath('//img[@class="hidden"]/@src')->text(),
            ]);
        });

        if (!empty($this->races)) {
            $this->cacheClient->set(self::CACHE_KEY_GRAND_PRIX_RESULT_URLS, $this->races, 172800 /*2 days*/);
        }
    }

    /**
     * @return array
     */
    public function fetchGrandPrix()
    {
        $crawler = $this->getData($this->getRacesUrl());

        $grandPrix = $crawler->filterXPath('//article[contains(@class, "fom-teaser")]');

        $this->racesInfo = [];

        $grandPrix->each(function (Crawler $race, $key) {
            $title = $race->filterXPath('//section/h4')->text();
            $slug = strtolower(str_replace(' ', '-', $title));
            $photo = $race->filterXPath('//img[@class="hidden"]/@src')->text();
            $date = explode('-', trim($race->filterXPath('//section/p[@class="teaser-date"]')->text()));
            $dateString = sprintf('%s-%s', trim(str_replace(' ', null, $date[0]), " \t\n\r\0\x0BOct"), trim($date[1]));

            $this->racesInfo[$this->hash($slug)] = [
                'title' => $title,
                'shortName' => str_replace('2015 FORMULA 1 ', null, $title),
                'slug'  => $slug,
                'photo' => self::HOST_URL . $photo,
                'fullSizePhoto' => self::HOST_URL . str_replace('img.320', 'img.1920', $photo),
                'date' => $dateString,
            ];
        });

        if (!empty($this->racesInfo)) {
            $this->cacheClient->set(self::CACHE_KEY_GRAND_PRIX, $this->racesInfo, 2592000 /*30 days*/);
        }
    }

    /**
     * @param array $stage
     *
     * @return array
     */
    public function fetchGrandPrixQualifyingResult(array $stage)
    {
        $results = $this->cacheClient->get(self::CACHE_KEY_QUALIFYING_RESULTS . $stage['url']);

        if (false === $results) {

            $crawler = $this->getData($this->getQualifyingResultUrl($stage));

            $rows = $crawler->filterXPath('//tr[position() != last()][position() != 1]');

            $results = [];

            foreach ($rows as $row) {
                /**@var \DomElement $row */

                $pilot = trim($row->getElementsByTagName('td')->item(1)->textContent);
                $team = $row->getElementsByTagName('td')->item(2)->textContent;

                $results[$this->hash($pilot)] = [
                    'position' => $row->getElementsByTagName('td')->item(0)->textContent,
                    'pilot'    => $pilot,
                    'hash'     => $this->hash($pilot),
                    'team'     => $team,
                    'engine'   => $this->getTeamEngine($team),
                ];
            }

            if (!empty($results)) {
                $this->cacheClient->set(self::CACHE_KEY_QUALIFYING_RESULTS . $stage['url'], $results, 120 /*2 minutes*/);
            }
        }

        return $results;
    }

    /**
     * @param array $stage
     *
     * @return array
     */
    public function fetchGrandPrixRaceResult(array $stage)
    {
        $results = $this->cacheClient->get(self::CACHE_KEY_RACE_RESULTS . $stage['url']);

        if (false === $results) {

            $crawler = $this->getData($this->getRaceResultUrl($stage));

            $rows = $crawler->filterXPath('//tr[position() != 1]');

            $results = [];

            foreach ($rows as $row) {
                /**@var \DomElement $row */

                $pilotNameBlock = $row->getElementsByTagName('td')->item(1);

                $pilot = trim($pilotNameBlock->childNodes->item(1)->textContent . $pilotNameBlock->childNodes->item(3)->textContent);
                $team = trim($row->getElementsByTagName('td')->item(3)->textContent);

                $results[$this->hash($pilot)] = [
                    'position' => trim($row->getElementsByTagName('td')->item(0)->textContent),
                    'pilot'    => $pilot,
                    'hash'     => $this->hash($pilot),
                    'team'     => $team,
                    'engine'   => $this->getTeamEngine($team),
                ];
            }

            if (!empty($results)) {
                $this->cacheClient->set(self::CACHE_KEY_RACE_RESULTS . $stage['url'], $results, 120 /*2 minutes*/);
            }
        }

        return $results;
    }

    /**
     * Fetches drivers data
     */
    protected function fetchDriversData()
    {
        $crawler = $this->getData($this->getDriversUrl());

        $figures = $crawler->filterXPath('//figure');

        $this->drivers = [];

        $figures->each(function(Crawler $item, $key) {
            $pilot = trim($item->filterXPath('//h1')->first()->text());

            $this->drivers[$this->hash($pilot)] = [
                'number'   => $item->filterXPath('//figcaption/div[@class="driver-number"]/span')->first()->text(),
                'fullname' => $pilot,
                'hash'     => $this->hash($pilot),
                'photo'    => self::HOST_URL . str_replace('img.1920', 'img.320', $item->filterXPath('//img/@src')->first()->text()),
                'team'     => $item->filterXPath('//figcaption/p[@class="driver-team"]/span')->first()->text(),
            ];
        });

        if (!empty($this->drivers)) {
            $this->cacheClient->set(self::CACHE_KEY_DRIVERS_DATA, $this->drivers, 2592000 /*30 days*/);
        }
    }

    /**
     * Fetches teams data
     */
    protected function fetchTeams()
    {
        $this->teams = [
            'Mercedes'    => [
                'title'  => 'Mercedes',
                'engine' => 'Mercedes',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/Mercedes/_jcr_content/teamCar.img.jpg',
            ],
            'Ferrari'     => [
                'title'  => 'Ferrari',
                'engine' => 'Ferrari',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/Ferrari/_jcr_content/teamCar.img.jpg',
            ],
            'Williams'    => [
                'title'  => 'Williams',
                'engine' => 'Mercedes',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/Williams/_jcr_content/teamCar.img.jpg',
            ],
            'Sauber'      => [
                'title'  => 'Sauber',
                'engine' => 'Ferrari',
                'photo'  => self::HOST_URL . '/etc/designs/fom-website/images/driver-standings/default.gif',
                //@TODO change
            ],
            'Red Bull'    => [
                'title'  => 'Red Bull',
                'engine' => 'Renault',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/Red-Bull/_jcr_content/teamCar.img.jpg',
            ],
            'Force India' => [
                'title'  => 'Force India',
                'engine' => 'Mercedes',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/Force-India/_jcr_content/teamCar.img.jpg',
            ],
            'Toro Rosso'  => [
                'title'  => 'Toro Rosso',
                'engine' => 'Renault',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/Toro-Rosso/_jcr_content/teamCar.img.jpg',
            ],
            'McLaren'     => [
                'title'  => 'McLaren',
                'engine' => 'Honda',
                'photo'  => self::HOST_URL . '/content/fom-website/en/championship/teams/McLaren/_jcr_content/teamCar.img.jpg',
            ],
            'Lotus'       => [
                'title'  => 'Lotus',
                'engine' => 'Mercedes',
                'photo'  => self::HOST_URL . '/etc/designs/fom-website/images/driver-standings/default.gif',
                //@TODO change
            ],
            'Marussia'    => [
                'title'  => 'Marussia',
                'engine' => 'Ferrari',
                'photo'  => self::HOST_URL . '/etc/designs/fom-website/images/driver-standings/default.gif',
                //@TODO change
            ],
        ];
    }

    /**
     * Fetches engines data
     */
    protected function fetchEngines()
    {
        $this->engines = [
            'Mercedes',
            'Ferrari',
            'Renault',
            'Honda',
        ];
    }

    /*
     * Helpers
     */

    /**
     * @param $team
     *
     * @return bool
     */
    protected function getTeamEngine($team)
    {
        return !empty($this->engines[$team])
            ? $this->engines[$team]
            : false
        ;
    }

    /**
     * @param $stage
     *
     * @return mixed
     */
    protected function getQualifyingResultUrl($stage)
    {
        return self::HOST_URL .  substr($stage['url'], 0, -5) . self::QUALIFYING_SITE;
    }

    /**
     * @param $stage
     *
     * @return mixed
     */
    protected function getRaceResultUrl($stage)
    {
        return self::HOST_URL .  substr($stage['url'], 0, -5) . self::RACE_SITE;
    }

    /**
     * @param array $raceData
     */
    protected function addRaceInfo(array $raceData)
    {
        $this->races[$this->hash($raceData['slug'])] = $raceData;
    }

    /**
     * @param $string
     *
     * @return string
     */
    protected function hash($string)
    {
        return md5($string);
    }

    /**
     * @return string
     */
    protected function getDriversUrl()
    {
        return self::HOST_URL . self::DRIVERS_URL;
    }

    /**
     * @return string
     */
    protected function getBaseResultsUrl()
    {
        return self::HOST_URL. self::RESULTS_BASE_URL;
    }

    /**
     * @return string
     */
    protected function getRacesUrl()
    {
        return self::HOST_URL . self::RACES_URL;
    }
}