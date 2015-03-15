<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    2015-03-15
 */
namespace Provider;

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

    /**
     * Contains all the available Grand Prix result URLs
     *
     * @var array
     */
    protected $races;

    /**
     * Initializes constructor
     */
    public function __construct()
    {
        $this->fetchGrandPrixResultURLs();
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

            $results[] = [
                'position' => $row->getElementsByTagName('td')->item(0)->textContent,
                'pilot'    => $row->getElementsByTagName('td')->item(1)->textContent,
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

            $results[] = [
                'position' => trim($row->getElementsByTagName('td')->item(0)->textContent),
                'pilot'    => $pilotNameBlock->childNodes->item(1)->textContent . $pilotNameBlock->childNodes->item(3)->textContent,
                'team'     => trim($row->getElementsByTagName('td')->item(3)->textContent),
            ];
        }

        dump($results);

        return $results;
    }

    /**
     * @param array $raceData
     */
    public function addRaceInfo(array $raceData)
    {
        $this->races[$this->hashRaceTitle($raceData['slug'])] = $raceData;
    }

    /**
     * @param $raceName
     *
     * @return array|bool
     */
    public function getRaceByName($raceName)
    {
        $raceHash = $this->hashRaceTitle($raceName);

        return !empty($this->races[$raceHash])
            ? $this->races[$raceHash]
            : false
        ;
    }

    /**
     * @param $raceTitle
     *
     * @return string
     */
    protected function hashRaceTitle($raceTitle)
    {
        return md5($raceTitle);
    }

    /**
     * @param $stage
     *
     * @return mixed
     */
    public function getQualifyingResultUrl($stage)
    {
        return self::HOST_URL .  substr($stage['url'], 0, -5) . self::QUALIFYING_SITE;
    }

    /**
     * @param $stage
     *
     * @return mixed
     */
    public function getRaceResultUrl($stage)
    {
        return self::HOST_URL .  substr($stage['url'], 0, -5) . self::RACE_SITE;
    }

    /**
     * @return string
     */
    protected function getBaseResultsUrl()
    {
        return self::HOST_URL. self::RESULTS_BASE_URL;
    }
}