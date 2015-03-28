<?php
/**
 * @author   Jokūbas Ramanauskas
 *
 * @since    2015-03-15
 */

namespace Provider;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Formula1.
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
     * Contains all the available Grand Prix result URLs.
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

        $this->getGrandPrixResultUrls();
        $this->getDriversData();
        $this->getTeams();
        $this->getEngines();
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

//        if (false === $cacheResults) {
            $this->fetchGrandPrixResultURLs();
//        } else {
//            $this->races = $cacheResults;
//        }

        return $this->races;
    }

    /**
     * @return array|mixed
     */
    public function getGrandPrix()
    {
        $cacheResults = $this->cacheClient->get(self::CACHE_KEY_GRAND_PRIX);

        if (false === $cacheResults) {
            $this->fetchGrandPrix();
        } else {
            $this->racesInfo = $cacheResults;
        }

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
        $crawler = $this->getData($this->getBaseResultsUrl().self::RACE_RESULTS_URL);

        if (false !== $crawler) {
            $grandPrix = $crawler->filterXPath('//div[@class="group article-columns"]/a[@class="column column-4"]');

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

        return false;
    }

    /**
     * @return bool
     */
    public function fetchGrandPrix()
    {
        $crawler = $this->getData($this->getRacesUrl());

        if (false !== $crawler) {
            $grandPrix = $crawler->filterXPath('//article[contains(@class, "fom-teaser")]');

            $this->racesInfo = [];

            $grandPrix->each(function (Crawler $race, $key) {
                $title = $race->filterXPath('//section/h4')->text();
                $slug = strtolower(str_replace(' ', '-', $title));
                $photo = $race->filterXPath('//img[@class="hidden"]/@src')->text();
                $date = explode('-', trim($race->filterXPath('//section/p[@class="teaser-date"]')->text()));
                $dateString = sprintf('%s-%s', trim(str_replace(' ', null, $date[0]), " \t\n\r\0\x0BOct"), trim($date[1]));

                $hash = $this->hash($slug);

                $this->racesInfo[$hash] = [
                    'title' => $title,
                    'shortName' => str_replace('2015 FORMULA 1 ', null, $title),
                    'slug'  => $slug,
                    'photo' => self::HOST_URL.$photo,
                    'fullSizePhoto' => self::HOST_URL.str_replace('img.320', 'img.1024', $photo),
                    'date' => $dateString,
                    'hash' => $hash,
                ];
            });

            if (!empty($this->racesInfo)) {
                $this->cacheClient->set(self::CACHE_KEY_GRAND_PRIX, $this->racesInfo, 2592000 /*30 days*/);
            }
        }

        return false;
    }

    /**
     * @param array $stage
     *
     * @return array
     */
    public function fetchGrandPrixQualifyingResult(array $stage)
    {
        $results = $this->cacheClient->get(self::CACHE_KEY_QUALIFYING_RESULTS.$stage['url']);

        if (false === $results) {
            $crawler = $this->getData($this->getQualifyingResultUrl($stage));

            if (false !== $crawler) {

                $rows = $crawler->filterXPath('//tr[position() != last()][position() != 1]');

                $results = [];

                foreach ($rows as $position => $row) {
                    /**@var \DomElement $row */

                    if (strlen($row->getElementsByTagName('td')->item(1)->textContent) <= 2) {
                        $pilot = trim($row->getElementsByTagName('td')->item(2)->textContent);
                        $team = $row->getElementsByTagName('td')->item(3)->textContent;
                    } else {
                        $pilot = trim($row->getElementsByTagName('td')->item(1)->textContent);
                        $team  = $row->getElementsByTagName('td')->item(2)->textContent;
                    }

                    $results[$this->hash($pilot)] = [
    //                    'position' => $row->getElementsByTagName('td')->item(0)->textContent,
                        'position' => $position + 1,
                        'pilot'    => $pilot,
                        'hash'     => $this->hash($pilot),
                        'team'     => $team,
                        'engine'   => $this->getEngineByTeam($team),
                    ];
                }

                if (!empty($results)) {
                    $this->cacheClient->set(self::CACHE_KEY_QUALIFYING_RESULTS.$stage['url'], $results, 120 /*2 minutes*/);
                }
            } else {
                return false;
            }

        }

        return $results;
    }

    /**
     * @param array $stage
     *
     * @return array|bool|mixed
     */
    public function fetchGrandPrixRaceResult(array $stage)
    {
        $results = $this->cacheClient->get(self::CACHE_KEY_RACE_RESULTS.$stage['url']);

        if (false === $results) {
            $crawler = $this->getData($this->getRaceResultUrl($stage));

            if (false !== $crawler) {
                $rows = $crawler->filterXPath('//tr[position() != 1]');

                $results = [];

                foreach ($rows as $position => $row) {
                    /**@var \DomElement $row */

                    $pilotNameBlock = $row->getElementsByTagName('td')->item(1);

                    $pilot = trim($pilotNameBlock->childNodes->item(1)->textContent.$pilotNameBlock->childNodes->item(3)->textContent);
                    $team = trim($row->getElementsByTagName('td')->item(3)->textContent);

                    $results[$this->hash($pilot)] = [
    //                    'position' => trim($row->getElementsByTagName('td')->item(0)->textContent),
                        'position' => $position + 1,
                        'pilot'    => $pilot,
                        'hash'     => $this->hash($pilot),
                        'team'     => $team,
                        'engine'   => $this->getEngineByTeam($team),
                    ];
                }

                if (!empty($results)) {
                    $this->cacheClient->set(self::CACHE_KEY_RACE_RESULTS.$stage['url'], $results, 120 /*2 minutes*/);
                }
            } else {
                return false;
            }
        }

        return $results;
    }

    /**
     * Fetches drivers data.
     */
    protected function fetchDriversData()
    {
        $crawler = $this->getData($this->getDriversUrl());

        $figures = $crawler->filterXPath('//figure');

        $this->drivers = [];

        $figures->each(function (Crawler $item, $key) {
            $pilot = trim($item->filterXPath('//h1')->first()->text());

            if ($pilot === "Kimi Räikkönen") {
                $pilot = "Kimi Raikkonen";
            }

            $this->drivers[$this->hash($pilot)] = [
                'number'   => $item->filterXPath('//figcaption/div[@class="driver-number"]/span')->first()->text(),
                'fullname' => $pilot,
                'hash'     => $this->hash($pilot),
                'photo'    => self::HOST_URL.str_replace('img.1920', 'img.140', $item->filterXPath('//img/@src')->first()->text()),
                'team'     => $item->filterXPath('//figcaption/p[@class="driver-team"]/span')->first()->text(),
            ];
        });

        if (!empty($this->drivers)) {
            $this->cacheClient->set(self::CACHE_KEY_DRIVERS_DATA, $this->drivers, 2592000 /*30 days*/);
        }
    }

    /**
     * Fetches teams data.
     */
    protected function fetchTeams()
    {
        $this->teams = [
            'Mercedes'    => [
                'title'  => 'Mercedes',
                'engine' => 'Mercedes',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Mercedes/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Mercedes/_jcr_content/logo.img.png/1425652811221.png',
//                'photo'  => 'http://www.vectorsland.com/imgd/l38759-mercedes-gp-petronas-f1-logo-30998.jpg',
            ],
            'Ferrari'     => [
                'title'  => 'Ferrari',
                'engine' => 'Ferrari',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Ferrari/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Ferrari/_jcr_content/logo.img.png/1424035994766.png',
//                'photo'  => 'http://us.wilogo.com/themes/wilogo/images/upload/562113878453.jpg',
            ],
            'Williams'    => [
                'title'  => 'Williams',
                'engine' => 'Mercedes',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Williams/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Williams/_jcr_content/logo.img.png/1424034752681.png',
//                'photo'  => 'https://pbs.twimg.com/profile_images/441547605168771074/SU9DcYVe.jpeg',
            ],
            'Sauber'      => [
                'title'  => 'Sauber',
                'engine' => 'Ferrari',
//                'photo'  => self::HOST_URL.'/etc/designs/fom-website/images/driver-standings/default.gif',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Sauber/_jcr_content/logo.img.png/1424036712372.png',
            ],
            'Red Bull Racing'    => [
                'title'  => 'Red Bull',
                'engine' => 'Renault',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Red-Bull/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Red-Bull/_jcr_content/logo.img.png/1424034222158.png',
            ],
            'Force India' => [
                'title'  => 'Force India',
                'engine' => 'Mercedes',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Force-India/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Force-India/_jcr_content/logo.img.png/1424036629759.png',
            ],
            'Toro Rosso'  => [
                'title'  => 'Toro Rosso',
                'engine' => 'Renault',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Toro-Rosso/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Toro-Rosso/_jcr_content/logo.img.png/1424036670890.png',
            ],
            'McLaren'     => [
                'title'  => 'McLaren',
                'engine' => 'Honda',
//                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/McLaren/_jcr_content/teamCar.img.jpg',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/McLaren/_jcr_content/logo.img.png/1424036219966.png',
            ],
            'Lotus'       => [
                'title'  => 'Lotus',
                'engine' => 'Mercedes',
//                'photo'  => self::HOST_URL.'/etc/designs/fom-website/images/driver-standings/default.gif',
                'photo'  => self::HOST_URL.'/content/fom-website/en/championship/teams/Lotus/_jcr_content/logo.img.png/1423427335332.png',
            ],
            'Marussia'    => [
                'title'  => 'Marussia',
                'engine' => 'Ferrari',
//                'photo'  => self::HOST_URL.'/etc/designs/fom-website/images/driver-standings/default.gif',
                'photo'  => 'http://www.gp3series.com/webimage/GP3-TeamLogoThumb/Global/GP3/Teams-Logo/Logo_MarussiaManor.png',
            ],
        ];
    }

    /**
     * Fetches engines data.
     */
    protected function fetchEngines()
    {
        $this->engines = [
            'Mercedes' => [
                'title' => 'Mercedes',
                'photo' => self::HOST_URL . '/content/fom-website/en/championship/teams/Mercedes/_jcr_content/logo.img.png/1425652811221.png',
            ],
            'Ferrari'  => [
                'title' => 'Ferrari',
                'photo' => self::HOST_URL . '/content/fom-website/en/championship/teams/Ferrari/_jcr_content/logo.img.png/1424035994766.png',
            ],
            'Renault'  => [
                'title' => 'Renault',
                'photo' => 'http://upload.wikimedia.org/wikipedia/commons/2/2d/Logo_Renault_Sport_F1.png',
            ],
            'Honda'    => [
                'title' => 'Honda',
                'photo' => self::HOST_URL . '/content/fom-website/en/championship/teams/McLaren/_jcr_content/logo.img.png/1424036219966.png',
            ],
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
    protected function getEngineByTeam($team)
    {
        return !empty($this->teams[$team])
            ? $this->engines[$this->teams[$team]['engine']]
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
        return self::HOST_URL.substr($stage['url'], 0, -5).self::QUALIFYING_SITE;
    }

    /**
     * @param $stage
     *
     * @return mixed
     */
    protected function getRaceResultUrl($stage)
    {
        return self::HOST_URL.substr($stage['url'], 0, -5).self::RACE_SITE;
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
        return self::HOST_URL.self::DRIVERS_URL;
    }

    /**
     * @return string
     */
    protected function getBaseResultsUrl()
    {
        return self::HOST_URL.self::RESULTS_BASE_URL;
    }

    /**
     * @return string
     */
    protected function getRacesUrl()
    {
        return self::HOST_URL.self::RACES_URL;
    }
}
