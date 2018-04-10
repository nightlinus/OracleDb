<?php
/**
 * Date: 02.12.15
 * Time: 15:38
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Statement;

use nightlinus\OracleDb\Config;
use nightlinus\OracleDb\Database;
use nightlinus\OracleDb\Driver\AbstractDriver;
use nightlinus\OracleDb\Profiler\Profiler;

class StatementFactory
{
    /**
     * @var StatementCache
     */
    private $cache;

    /**
     * @var AbstractDriver
     */
    private $driver;

    /**
     * @var Profiler
     */
    private $profiler;

    public function __construct(StatementCache $cache, AbstractDriver $driver, Profiler $profiler)
    {
        $this->cache = $cache;
        $this->driver = $driver;
        $this->profiler = $profiler;
    }


    public function make($queryString, Database $db): Statement
    {
        $statementCacheEnabled = $db->config(Config::STATEMENT_CACHE_ENABLED);
        $statement = null;

        if ($statementCacheEnabled) {
            $statement = $this->cache->get($queryString);
        }

        $connection = $db->getConnection();
        $statement = $statement ?: new Statement(
            $queryString,
            $connection,
            $this->driver,
            $this->profiler,
            $db->config(Config::STATEMENT_RETURN_TYPE),
            $db->config(Config::STATEMENT_AUTOCOMMIT)
        );

        if ($statementCacheEnabled) {
            $this->cache->add($statement);
        }

        return $statement;
    }
}
