<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    2015-03-15
 */
namespace Provider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * Class Formula1
 * @package Provider
 */
class Formula1 extends BaseProvider
{
    const HOST_URL = "http://www.formula1.com";

    const RESULTS_BASE_URL = "/content/fom-website/en/championship/results/";

    const RACE_RESULTS_URL = "2015-race-results.html";

    const QUALIFYING_SITE = '/qualifying.html';

    const RACE_SITE = '/race.html';

    const DRIVERS_URL = "/content/fom-website/en/championship/drivers.html";

    /**
     * Contains all the available Grand Prix result URLs
     *
     * @var array
     */
    protected $races;

    /** @var  array */
    protected $drivers;

    /** @var  array */
    protected $teams;

    /** @var  array */
    protected $engines;

    /**
     * Initializes constructor
     */
    public function __construct()
    {
        $this->fetchGrandPrixResultURLs();
        $this->fetchDriversData();
    }

    /**
     * @return array
     */
    public function fetchGrandPrixResultURLs()
    {
        $crawler = $this->getData($this->getBaseResultsUrl() . self::RACE_RESULTS_URL);

        $grandPrix = $crawler->filterXPath('//div[@class="group article-columns"]');

        $this->races = [];

        foreach ($grandPrix as $race) {
            /**@var \DomElement $race */

            $title = $race->getElementsByTagName('h4')->item(0)->textContent;

            $this->addRaceInfo([
                'url'   => $race->getElementsByTagName('a')->item(0)->attributes->getNamedItem('href')->textContent,
                'title' => $title,
                'slug' => strtolower(str_replace(' ', '-', $title)),
            ]);
        }
    }

    /**
     * @return array
     */
    public function getGrandPrixResultUrls()
    {
        if (true /*cache invalid*/) {
            $this->fetchGrandPrixResultURLs();
        }

        return $this->races;
    }

    /**
     * @param array $stage
     *
     * @return array
     */
    public function fetchGrandPrixQualifyingResult(array $stage)
    {
        $crawler = $this->getData($this->getQualifyingResultUrl($stage));

        $rows = $crawler->filterXPath('//tr[position() != last()][position() != 1]');

        $results = [];

        foreach ($rows as $row) {
            /**@var \DomElement $row */

            $pilot = $row->getElementsByTagName('td')->item(1)->textContent;

            $results[$this->hash($pilot)] = [
                'position' => $row->getElementsByTagName('td')->item(0)->textContent,
                'pilot'    => $pilot,
                'team'     => $row->getElementsByTagName('td')->item(2)->textContent,
            ];
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
        $crawler = $this->getData($this->getRaceResultUrl($stage));

        $rows = $crawler->filterXPath('//tr[position() != 1]');

        $results = [];

        foreach ($rows as $row) {
            /**@var \DomElement $row */

            $pilotNameBlock = $row->getElementsByTagName('td')->item(1);

            $pilot = $pilotNameBlock->childNodes->item(1)->textContent . $pilotNameBlock->childNodes->item(3)->textContent;

            $results[$this->hash($pilot)] = [
                'position' => trim($row->getElementsByTagName('td')->item(0)->textContent),
                'pilot'    => $pilot,
                'team'     => trim($row->getElementsByTagName('td')->item(3)->textContent),
            ];
        }

        return $results;
    }


    /**
     * @return array
     */
    public function getDriversData()
    {
        if (true /*cache invalid*/) {
            $this->fetchDriversData();
        }

        return $this->drivers;
    }

    /**
     * @return array
     */
    public function getTeams()
    {
        if (true /*cache invalid*/) {
            $this->fetchTeams();
        }

        return $this->teams;
    }

    /**
     * @return array
     */
    public function getEngines()
    {
        if (true /*cache invalid*/) {
            $this->fetchEngines();
        }

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
            : false
        ;
    }

    /**
     * Fetches drivers data
     */
    protected function fetchDriversData()
    {
        $crawler = $this->getData($this->getDriversUrl());

        $figures = $crawler->filterXPath('//figure');

        $this->drivers = [];

        $figures->each(function (Crawler $item, $key) {
            $pilot = $item->filterXPath('//h1')->first()->text();

            $this->drivers[$this->hash($pilot)] = [
                'number'   => $item->filterXPath('//figcaption/div[@class="driver-number"]/span')->first()->text(),
                'fullname' => $pilot,
                'photo'    => self::HOST_URL . str_replace('img.1920', 'img.320', $item->filterXPath('//img/@src')->first()->text()),
                'team'     => $item->filterXPath('//figcaption/p[@class="driver-team"]/span')->first()->text(),
            ];
        });
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
}