<?php
/**
 * Class for capture timing profiles for sql queries
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Profiler;

/**
 * Class Profiler
 */
class Profiler
{

    /**
     * Index of current profile
     *
     * @type int
     */
    protected $currentIndex = 0;

    /**
     * Container for profiles
     *
     * @type Entry[]
     */
    protected $profiles = [ ];

    /**
     * Method to finish current profiling.
     *
     * @return $this
     */
    public function end()
    {
        if (isset($this->profiles[ $this->currentIndex ])) {
            $current = &$this->profiles[ $this->currentIndex ];
            $current->endTime = microtime(true);
            $current->executeDuration = $current->endTime - $current->startTime;
            $this->currentIndex++;
        }

        return $this;
    }

    /**
     * Method to get last profile
     *
     * @return array|null
     */
    public function getLastProfile()
    {
        return end($this->profiles);
    }

    /**
     * Return full stack of profiles
     *
     * @return array
     */
    public function getProfiles()
    {
        return $this->profiles;
    }

    /**
     * Function that start profiling code
     *
     * @param       $sql
     * @param array $data
     *
     * @return $this
     */
    public function start($sql, array $data = null)
    {
        $this->profiles[ $this->currentIndex ] = new Entry($this->currentIndex, $sql, $data);

        return $this->currentIndex;
    }

    /**
     * Start profiling fetching time
     *
     * @param $profileId
     *
     * @return $this
     */
    public function startFetch($profileId)
    {
        $this->profiles[ $profileId ]->lastFetchStart = microtime(true);

        return $this;
    }

    /**
     * Stop profiling fetching time
     *
     * @param $profileId
     *
     * @return $this
     */
    public function stopFetch($profileId)
    {
        $this->profiles[ $profileId ]->fetchDuration += microtime(true) - $this->profiles[ $profileId ]->lastFetchStart;

        return $this;
    }
}
