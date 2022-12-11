<?php
/**
 * Date: 11.09.14
 * Time: 12:32
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb;

use nightlinus\OracleDb\Statement\Statement;
use nightlinus\OracleDb\Statement\StatementCache;
use function array_key_exists;
use function func_num_args;
use function is_array;
use const OCI_DEFAULT;

/**
 * Class Config
 * It stores and manipilate client configuration
 */
class Config implements \ArrayAccess
{
    /**
     * Client variables
     */
    public const CLIENT_IDENTIFIER = 'client.identifier';
    public const CLIENT_INFO = 'client.info';
    public const CLIENT_MODULE_NAME = 'client.module_name';
    public const CLIENT_UPDATE_MODULE_AND_ACTION = 'client.update_module_and_action';

    /**
     * Connection variables
     */
    public const CONNECTION_CACHE = 'connection.cache';
    public const CONNECTION_CHARSET = 'connection.charset';
    public const CONNECTION_CLASS = 'connection.class';
    public const CONNECTION_EDITION = 'connection.edition';
    public const CONNECTION_PASSWORD = 'connection.password';
    public const CONNECTION_PERSISTENT = 'connection.persistent';
    public const CONNECTION_PRIVILEGED = 'connection.privileged';
    public const CONNECTION_STRING = 'connection.string';
    public const CONNECTION_USER = 'connection.user';

    public const DRIVER_CLASS = 'driver.class';

    public const GROUP_CLIENT = 'client';
    public const GROUP_CONNECTION = 'connection';
    public const GROUP_PROFILER = 'profiler';
    public const GROUP_SESSION = 'session';

    /**
     * BasicProfiler variables
     */
    public const PROFILER_CLASS = 'profiler.class';
    public const PROFILER_ENABLED = 'profiler.enabled';

    /**
     * Session variables
     */
    public const SESSION_CLASS = 'session.class';
    public const SESSION_CURRENT_SCHEMA = 'session.schema';
    public const SESSION_DATE_FORMAT = 'session.date_format';
    public const SESSION_DATE_LANGUAGE = 'session.date_language';

    /**
     *  Statement variables
     */
    public const STATEMENT_AUTOCOMMIT = 'statement.autocommit';
    public const STATEMENT_CACHE_CLASS = 'statement_cache.class';
    public const STATEMENT_CACHE_ENABLED = 'statement_cache.enabled';
    public const STATEMENT_CACHE_SIZE = 'statement_cache.size';
    public const STATEMENT_RETURN_TYPE = 'statement.return_type';

    /**
     * Array to store current values of configuration entrys
     *
     * @var array
     */
    protected $config = [];

    /**
     * Array to store default configuretion values
     *
     * @var array
     */
    protected $defaults = [
        self::SESSION_DATE_FORMAT => 'DD.MM.YYYY HH24:MI:SS',
        self::SESSION_DATE_LANGUAGE => null,
        self::SESSION_CURRENT_SCHEMA => null,
        self::SESSION_CLASS => Session\Oracle::class,
        self::CONNECTION_CHARSET => 'AL32UTF8',
        self::CONNECTION_PERSISTENT => false,
        self::CONNECTION_PRIVILEGED => OCI_DEFAULT,
        self::CONNECTION_CACHE => false,
        self::CONNECTION_CLASS => null,
        self::CONNECTION_EDITION => null,
        self::CONNECTION_USER => null,
        self::CONNECTION_PASSWORD => null,
        self::CONNECTION_STRING => null,
        self::CLIENT_IDENTIFIER => null,
        self::CLIENT_INFO => null,
        self::CLIENT_MODULE_NAME => null,
        self::CLIENT_UPDATE_MODULE_AND_ACTION => false,
        self::PROFILER_ENABLED => false,
        self::PROFILER_CLASS => Profiler\BasicProfiler::class,
        self::STATEMENT_AUTOCOMMIT => false,
        self::STATEMENT_RETURN_TYPE => Statement::RETURN_ARRAY,
        self::STATEMENT_CACHE_ENABLED => true,
        self::STATEMENT_CACHE_SIZE => 50,
        self::STATEMENT_CACHE_CLASS => StatementCache::class,
        self::DRIVER_CLASS => Driver\Oracle::class,
    ];

    /**
     * @param array $configuration
     */
    private function __construct(array $configuration = [])
    {
        foreach ($configuration as $key => $value) {
            $this->set($key, $value);
        }

        $this->validate();
    }

    public static function fromArray(array $config = [])
    {
        return new self($config);
    }

    /**
     * @param string|array $name
     * @param null         $value
     *
     * @return mixed|null
     * @throws Exception
     */
    public function config($name, $value = null)
    {
        if (func_num_args() === 1) {
            if (is_array($name)) {
                $this->set($name);
            } else {
                return $this->get($name);
            }
        } else {
            $this->set($name, $value);
        }

        return $value;
    }

    /**
     * Get value from config entry
     *
     * @param string $key
     *
     * @return mixed
     * @throws Exception
     */
    public function get($key)
    {
        if (array_key_exists($key, $this->config)) {
            $value = $this->config[ $key ];
        } elseif ($this->offsetExists($key)) {
            $value = $this->defaults[ $key ];
        } else {
            throw new Exception("Config entry «{$key}» doesn't exists. ");
        }

        return $value;
    }

    /**
     * Whether a offset exists
     *
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     *
     * @param mixed $offset
     * An offset to check for.
     *
     * @return boolean true on success or false on failure.
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->defaults);
    }

    /**
     * Offset to retrieve
     *
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     *
     * @param mixed $offset
     * The offset to retrieve.
     *
     * @return mixed Can return all value types.
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     *
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     *
     * @param mixed $offset
     * The offset to assign the value to.
     * @param mixed $value
     * The value to set.
     *
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     *
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     *
     * @param mixed $offset
     * The offset to unset.
     *
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->config[ $offset ]);
    }

    /**
     * Sets config entry, if array is given,
     * apply self to each key value pair of array
     *
     * @param string|array $key
     * @param mixed        $value
     *
     * @throws Exception
     */
    public function set($key, $value = null)
    {
        if (func_num_args() === 1 && is_array($key)) {
            foreach ($key as $name => $value) {
                $this->set($name, $value);
            }

            return;
        }

        if ($this->offsetExists($key)) {
            $this->config[ $key ] = $value;
        } else {
            throw new Exception("Config entry «{$key}» doesn't exists. ");
        }
    }

    /**
     * Validates config values
     *
     * @return bool
     * @throws \nightlinus\OracleDb\Exception
     */
    public function validate()
    {
        if ($this->get(self::CONNECTION_USER) === null) {
            throw new Exception("User name is not specified");
        }
        if ($this->get(self::CONNECTION_PASSWORD) === null) {
            throw new Exception("Password is not specified");
        }

        return true;
    }
}
