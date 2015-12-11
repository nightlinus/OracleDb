<?php
/**
 * Date: 11.12.15
 * Time: 12:25
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb\Profiler;

class DisabledProfiler implements Profiler
{

    /**
     * @inheritdoc
     */
    public function end()
    {
    }

    /**
     * @inheritdoc
     */
    public function lastProfile()
    {
    }

    /**
     * @inheritdoc
     */
    public function profiles()
    {
    }

    /**
     * @inheritdoc
     */
    public function start($sql, array $data = null)
    {
    }

    /**
     * @inheritdoc
     */
    public function startFetch($profileId)
    {
    }

    /**
     * @inheritdoc
     */
    public function stopFetch($profileId)
    {
    }
}
