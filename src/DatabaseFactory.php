<?php
/**
 * Date: 10.12.15
 * Time: 14:33
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb;

use nightlinus\OracleDb\Profiler\DisabledProfiler;
use nightlinus\OracleDb\Statement\StatementFactory;
use function is_string;

class DatabaseFactory
{
    private function __construct()
    {
    }

    public static function fromConfig(Config $config): Database
    {
        return self::make($config);
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    public static function fromCredentials(
        string $userName,
        string $password = '',
        string $connectionString = '',
        array $config = []
    ): Database {
        $config[ Config::CONNECTION_USER ] = $userName;
        $config[ Config::CONNECTION_PASSWORD ] = $password;
        $config[ Config::CONNECTION_STRING ] = $connectionString;

        return self::make(Config::fromArray($config));
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

        return is_string($driverClass) ? new $driverClass() : $driverClass;
    }

    private static function makeStatementCache(Config $config)
    {
        $class = $config->get(Config::STATEMENT_CACHE_CLASS);
        $cacheSize = $config->get(Config::STATEMENT_CACHE_SIZE);

        return is_string($class) ? new $class($cacheSize) : $class;
    }

    private static function make(Config $config): Database
    {
        $cache = self::makeStatementCache($config);
        $driver = self::makeDriver($config);
        $profiler = self::makeProfiler($config);

        $factory = new StatementFactory($cache, $driver, $profiler);

        return new Database($factory, $config, $driver);
    }
}
