<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    2015-03-15
 */
namespace Manager;


/**
 * Class DataManager
 * @package Manager
 */
class DataManager 
{
    /** @var  \Provider\Formula1 */
    protected $formula1Provider;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->formula1Provider = new \Provider\Formula1();
    }

    /**
     * @return array
     */
    public function getGrandPrix()
    {
        return $this->formula1Provider->getGrandPrixResultUrls();
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