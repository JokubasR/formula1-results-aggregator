<?php
/**
 * @author   Jokūbas Ramanauskas
 *
 * @since    2015-03-15
 */

namespace Manager;

use Beryllium\Cache\Cache;

/**
 * Class DataManager.
 */
class DataManager
{
    /** @var  \Provider\Formula1 */
    protected $formula1Provider;

    /** @var \Beryllium\Cache\Cache */
    protected $cacheProvider;

    /**
     * @param \Beryllium\Cache\Cache $cacheClient
     */
    public function __construct(Cache $cacheClient)
    {
        $this->cacheProvider = $cacheClient;

        $this->formula1Provider = new \Provider\Formula1($cacheClient);
    }

    /**
     * @return array
     */
    public function getGrandPrix()
    {
        return $this->formula1Provider->getGrandPrix();
    }

    /**
     * @param $stageName
     *
     * @return array
     */
    public function getGrandPrixQualifyingResult($stageName)
    {
        $stage = $this->getStageByName($stageName);

        return $this->formula1Provider->fetchGrandPrixQualifyingResult($stage);
    }

    /**
     * @param $stageName
     *
     * @return array
     */
    public function getGrandPrixRaceResult($stageName)
    {
        $stage = $this->getStageByName($stageName);

        return $this->formula1Provider->fetchGrandPrixRaceResult($stage);
    }

    /**
     * @return array
     */
    public function getDrivers()
    {
        return $this->formula1Provider->getDriversData();
    }

    /**
     * @return array
     */
    public function getTeams()
    {
        return $this->formula1Provider->getTeams();
    }

    /**
     * @return array
     */
    public function getEngines()
    {
        return $this->formula1Provider->getEngines();
    }

    /**
     * @return mixed
     */
    public function getCurrentRace()
    {
        $races = $this->formula1Provider->getGrandPrix();

        return array_shift($races);
    }

    /**
     * @param string $pilot1Hash
     * @param string $pilot2Hash
     * @param string $teamHash
     * @param string $engineHash
     *
     * @return array
     */
    public function getTeamByHashes($pilot1Hash, $pilot2Hash, $teamHash, $engineHash)
    {
        return [
            'pilot1' => $this->getDrivers()[$pilot1Hash],
            'pilot2' => $this->getDrivers()[$pilot2Hash],
            'team' => $this->getTeams()[$teamHash],
            'engine' => $this->getEngines()[$engineHash],
        ];
    }

    /**
     * @param $stageName
     *
     * @return array|bool
     */
    public function getStageByName($stageName)
    {
        return $this->formula1Provider->getRaceByName($stageName);
    }
}
