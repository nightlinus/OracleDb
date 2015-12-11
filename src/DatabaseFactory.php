<?php
/**
 * Date: 10.12.15
 * Time: 14:33
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb;

use nightlinus\OracleDb\Profiler\DisabledProfiler;
use nightlinus\OracleDb\Statement\StatementFactory;

class DatabaseFactory
{
    private function __construct()
    {
    }

    public static function fromConfig(Config $config)
    {
        return self::make($config);
    }

    public static function fromCredentials($userName, $password = '', $connectionString = '', $config = [ ])
    {
        $config[ Config::CONNECTION_USER ] = $userName;
        $config[ Config::CONNECTION_PASSWORD ] = $password;
        $config[ Config::CONNECTION_STRING ] = $connectionString;
        $config = Config::fromArray($config);

        return self::make($config);
    }

    private static function makeProfiler(Config $config)
    {
        if (!$config->get(Config::PROFILER_ENABLED)) {
            return new DisabledProfiler();
        }

        $class = $config->get(Config::PROFILER_CLASS);

        return is_string($class) ? new $class : $class;
    }

    private static function makeDriver(Config $config)
    {
        $driverClass = $config->get(Config::DRIVER_CLASS);
        $driver = is_string($driverClass) ? new $driverClass() : $driverClass;

        return $driver;
    }

    private static function makeStatementCache(Config $config)
    {
        $class = $config->get(Config::STATEMENT_CACHE_CLASS);
        $cacheSize = $config->get(Config::STATEMENT_CACHE_SIZE);
        $cache = is_string($class) ? new $class($cacheSize) : $class;

        return $cache;
    }

    private static function make(Config $config)
    {
        $cache = self::makeStatementCache($config);
        $driver = self::makeDriver($config);
        $profiler = self::makeProfiler($config);

        $factory = new StatementFactory($cache, $driver, $profiler);

        return new Database($factory, $config, $driver, $profiler);
    }
}
