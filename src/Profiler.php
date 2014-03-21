<?php
/**
 * Class that include databese statement functionality
 * this is wrapper above php oci extension.
 *
 * PHP version 5.5
 *
 * @category Database
 * @package  OracleDb
 * @author   Ogarkov Mikhail <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  GIT: 1
 * @link     http://github.com
 */

namespace OracleDb;

/**
 * Class Profiler
 * @package OracleDb
 */
class Profiler
{

    /**
     * Container for profiles
     *
     * @var array
     */
    protected $profiles = [];

    /**
     * Index of current profile
     *
     * @var int
     */
    protected $currentIndex = 0;

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
        $profileInformation = [
            'sql'        => $sql,
            'parameters' => $data,
            'start'      => microtime(true),
            'end'        => null,
            'elapsed'     => null,
            'stack'      => null
        ];

        $e = new Exception;
        $profileInformation['stack'] = $e->getTrace();
        $this->profiles[ $this->currentIndex ] = $profileInformation;

        return $this->currentIndex;
    }

    /**
     * Method to finish current profiling.
     *
     * @return $this
     * @throws Exception
     */
    public function end()
    {
        if (!isset($this->profiles[ $this->currentIndex ])) {
            throw new Exception('A profile must be started before ' . __FUNCTION__ . ' can be called.');
        }
        $current = & $this->profiles[ $this->currentIndex ];
        $current[ 'end' ] = microtime(true);
        $current[ 'elapsed' ] = $current[ 'end' ] - $current[ 'start' ];
        $this->currentIndex++;

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
}
