<?php
/**
 * Date: 11.12.15
 * Time: 12:23
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb\Profiler;

interface Profiler
{
    /**
     * Method to finish current profiling.
     */
    public function end();

    /**
     * Method to get last profile
     *
     * @return Entry|null
     */
    public function lastProfile();

    /**
     * Return full stack of profiles
     *
     * @return Entry[]
     */
    public function profiles();

    /**
     * Function that start profiling code
     *
     * @param       $sql
     * @param array $data
     *
     * @return $this
     */
    public function start($sql, array $data = null);

    /**
     * Start profiling fetching time
     *
     * @param $profileId
     *
     * @return $this
     */
    public function startFetch($profileId);

    /**
     * Stop profiling fetching time
     *
     * @param $profileId
     *
     * @return $this
     */
    public function stopFetch($profileId);
}
