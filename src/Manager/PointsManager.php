<?php
/**
 * @author   Jokūbas Ramanauskas
 *
 * @since    2015-03-15
 */

namespace Manager;

/**
 * Class PointsManager.
 */
class PointsManager
{
    const MODE_QUALIFYING = 1;
    const MODE_RACE = 2;

    const POINTS_MULTIPLIER_DRIVER = 1;
    const POINTS_MULTIPLIER_TEAM = 0.8;
    const POINTS_MULTIPLIER_ENGINE = 0.2;

    /** @var  \Provider\Formula1 */
    protected $formula1Provider;

    /** @var \Beryllium\Cache\Cache */
    protected $cacheClient;

    /**
     * Place => points.
     *
     * @var array
     */
    protected $pointsForQualifying = array(
        1 => 10,
        2 => 8,
        3 => 6,
        4 => 5,
        5 => 4,
        6 => 3,
        7 => 2,
        8 => 1,
    );

    /**
     * Place => points.
     *
     * @var array
     */
    protected $pointsForRace = array(
        1  => 25,
        2  => 18,
        3  => 15,
        4  => 12,
        5  => 10,
        6  => 8,
        7  => 6,
        8  => 5,
        9  => 4,
        10 => 3,
        11 => 2,
        12 => 1,
    );

    /**
     * @param \Beryllium\Cache\Cache $cacheClient
     */
    public function __construct(\Beryllium\Cache\Cache $cacheClient)
    {
        $this->cacheClient = $cacheClient;

        $this->formula1Provider = new \Provider\Formula1($cacheClient);
    }

    /**
     * @param array|boolean $stage
     * @param array $team
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function getStagePoints($stage, array $team)
    {
        if (false === $stage) {
            return false;
        }

        $results = [];

        $qualificationResults = $this->formula1Provider->fetchGrandPrixQualifyingResult($stage);
        if (empty($qualificationResults)) {
            return false;
        }

        $results['qualifying'] = $this->getCalculatedPoints($qualificationResults, $team['pilot1'], $team['pilot2'], $team['team'], $team['engine'], self::MODE_QUALIFYING);

        $raceResults = $this->formula1Provider->fetchGrandPrixRaceResult($stage);
        if (empty($raceResults)) {
            $results['total'] = $results['qualifying']['total'];
            return $results;
        }

        $results['race'] = $this->getCalculatedPoints($raceResults, $team['pilot1'], $team['pilot2'], $team['team'], $team['engine'], self::MODE_RACE);

        $results['total'] = $results['qualifying']['total'] + $results['race']['total'];

        return $results;
    }

    /**
     * @param array $results
     * @param array $firstPilot
     * @param array $secondPilot
     * @param array $team
     * @param       $engine
     * @param       $mode
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCalculatedPoints(array $results, array $firstPilot, array $secondPilot, array $team, $engine, $mode)
    {
        if (!empty($results[$firstPilot['hash']])) {
            $firstPilotData = $results[$firstPilot['hash']];
        } else {
            throw new \Exception(sprintf("Pilot %s not found", $firstPilot['fullname']));
        }

        if (!empty($results[$secondPilot['hash']])) {
            $secondPilotData = $results[$secondPilot['hash']];
        } else {
            throw new \Exception(sprintf("Pilot %s not found", $secondPilot['fullname']));
        }

        $points = [
            'pilot1' => $this->getPilotPoints($firstPilotData['position'], $mode),
            'pilot2' => $this->getPilotPoints($secondPilotData['position'], $mode),
            'team'   => 0,
            'engine' => 0,
        ];

        foreach ($results as $pilot) {
            if ($pilot['team'] === $team['title']) {
                $points['team'] += $this->getTeamPoints($pilot['position'], $mode);
            }

            if ($pilot['engine']['title'] === $engine['title']) {
                $points['engine'] += $this->getEnginePoints($pilot['position'], $mode);
            }
        }

        $points['total'] = $points['pilot1'] + $points['pilot2'] + $points['team'] + $points['engine'];

        return $points;
    }

    /**
     * @see \Manager\PointsManager::MODE_QUALIFYING
     * @see \Manager\PointsManager::MODE_RACE
     *
     * @param int $position
     * @param int $mode
     *
     * @return int
     */
    protected function getPoints($position, $mode)
    {
        return $mode === self::MODE_QUALIFYING
            ? $this->getQualifyingPoints($position)
            : $this->getRacePoints($position)
        ;
    }

    /**
     * @see \Manager\PointsManager::MODE_QUALIFYING
     * @see \Manager\PointsManager::MODE_RACE
     *
     * @param int $position
     * @param int $mode
     *
     * @return float
     */
    public function getPilotPoints($position, $mode)
    {
        return $this->getPoints($position, $mode) * self::POINTS_MULTIPLIER_DRIVER;
    }

    /**
     * @see \Manager\PointsManager::MODE_QUALIFYING
     * @see \Manager\PointsManager::MODE_RACE
     *
     * @param int $position
     * @param int $mode
     *
     * @return float
     */
    public function getTeamPoints($position, $mode)
    {
        return $this->getPoints($position, $mode) * self::POINTS_MULTIPLIER_TEAM;
    }

    /**
     * @see \Manager\PointsManager::MODE_QUALIFYING
     * @see \Manager\PointsManager::MODE_RACE
     *
     * @param int $position
     * @param int $mode
     *
     * @return float
     */
    public function getEnginePoints($position, $mode)
    {
        return $this->getPoints($position, $mode) * self::POINTS_MULTIPLIER_ENGINE;
    }

    /**
     * @param $position
     *
     * @return int
     */
    protected function getQualifyingPoints($position)
    {
        return array_key_exists($position, $this->pointsForQualifying)
            ? $this->pointsForQualifying[$position]
            : 0;
    }

    /**
     * @param $position
     *
     * @return int
     */
    protected function getRacePoints($position)
    {
        return array_key_exists($position, $this->pointsForRace)
            ? $this->pointsForRace[$position]
            : 0;
    }
}
