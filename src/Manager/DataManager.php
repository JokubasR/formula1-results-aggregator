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
     * @param $stageName
     *
     * @return array|bool
     */
    protected function getStageByName($stageName)
    {
        return $this->formula1Provider->getRaceByName($stageName);
    }
}
