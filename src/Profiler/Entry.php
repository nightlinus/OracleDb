<?php
/**
 * Date: 10.10.14
 * Time: 21:45
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Profiler;

/**
 * Class Entry
 */
class Entry
{

    public $id;

    public $sqlText;

    public $bindings;

    public $startTime;

    public $endTime;

    public $executeDuration;

    public $fetchDuration;

    public $trace;

    public $lastFetchStart;

    /**
     * @param int    $id
     * @param string $sql
     * @param array  $bindings
     */
    public function __construct($id, $sql, $bindings)
    {
        $this->id = $id;
        $this->sqlText = $sql;
        $this->bindings = $bindings;
        $this->startTime = microtime(true);
        $this->trace = (new \Exception())->getTrace();
    }
}
