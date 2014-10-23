<?php
/**
 * Date: 11.09.14
 * Time: 12:32
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  0.1.0
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb;

/**
 * Class Config
 * It stores and manipilate client configuration
 *
 * @package nightlinus\OracleDb
 */
class Config implements \ArrayAccess
{
    /**
     * Client variables
     */
    const CLIENT_IDENTIFIER  = 'client.identifier';
    const CLIENT_INFO        = 'client.info';
    const CLIENT_MODULE_NAME = 'client.module_name';

    /**
     * Connection variables
     */
    const CONNECTION_CACHE      = 'connection.cache';
    const CONNECTION_CHARSET    = 'connection.charset';
    const CONNECTION_CLASS      = 'connection.class';
    const CONNECTION_EDITION    = 'connection.edition';
    const CONNECTION_PASSWORD   = 'connection.password';
    const CONNECTION_PERSISTENT = 'connection.persistent';
    const CONNECTION_PRIVILEGED = 'connection.privileged';
    const CONNECTION_STRING     = 'connection.string';
    const CONNECTION_USER       = 'connection.user';

    const DRIVER_CLASS = 'driver.class';

    const GROUP_CLIENT     = 'client';
    const GROUP_CONNECTION = 'connection';
    const GROUP_PROFILER   = 'profiler';
    const GROUP_SESSION    = 'session';

    /**
     * Profiler variables
     */
    const PROFILER_CLASS   = 'profiler.class';
    const PROFILER_ENABLED = 'profiler.enabled';

    /**
     * Session variables
     */
    const SESSION_CURRENT_SCHEMA = 'session.CURRENT_SCHEMA';
    const SESSION_DATE_FORMAT    = 'session.NLS_DATE_FORMAT';
    const SESSION_DATE_LANGUAGE  = 'session.NLS_DATE_LANUAGE';

    /**
     *  Statement variables
     */
    const STATEMENT_AUTOCOMMIT    = 'statement.autocommit';
    const STATEMENT_CACHE_CLASS   = 'statement_cache.class';
    const STATEMENT_CACHE_ENABLED = 'statement_cache.enabled';
    const STATEMENT_CACHE_SIZE    = 'statement_cache.size';
    const STATEMENT_RETURN_TYPE   = 'statement.return_type';

    /**
     * Array to store current values of configuration entrys
     *
     * @type array
     */
    protected $config = [ ];

    /**
     * Array to store default configuretion values
     *
     * @type array
     */
    protected $defaults = [
        self::SESSION_DATE_FORMAT     => 'DD.MM.YYYY HH24:MI:SS',
        self::SESSION_DATE_LANGUAGE   => null,
        self::SESSION_CURRENT_SCHEMA  => null,
        self::CONNECTION_CHARSET      => 'AL32UTF8',
        self::CONNECTION_PERSISTENT   => false,
        self::CONNECTION_PRIVILEGED   => OCI_DEFAULT,
        self::CONNECTION_CACHE        => false,
        self::CONNECTION_CLASS        => null,
        self::CONNECTION_EDITION      => null,
        self::CONNECTION_USER         => null,
        self::CONNECTION_PASSWORD     => null,
        self::CONNECTION_STRING       => null,
        self::CLIENT_IDENTIFIER       => null,
        self::CLIENT_INFO             => null,
        self::CLIENT_MODULE_NAME      => null,
        self::PROFILER_ENABLED        => false,
        self::PROFILER_CLASS          => Profiler\Profiler::class,
        self::STATEMENT_AUTOCOMMIT    => false,
        self::STATEMENT_RETURN_TYPE   => Statement::RETURN_ARRAY,
        self::STATEMENT_CACHE_ENABLED => true,
        self::STATEMENT_CACHE_SIZE    => 50,
        self::STATEMENT_CACHE_CLASS   => StatementCache::class,
        self::DRIVER_CLASS            => Driver\Oracle::class
    ];

    /**
     * @param array $configuration
     */
    public function __construct(array $configuration = [ ])
    {
        foreach ($configuration as $key => $value) {
            $this->set($key, $value);
        }
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
    public function offsetExists($offset)
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
    public function offsetGet($offset)
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
    public function offsetSet($offset, $value)
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
    public function offsetUnset($offset)
    {
        unset($this->config[ $offset ]);
    }

    /**
     * Sets config entry, if array is given,
     * apply self to each key value pair of array
     *
     * @param string $key
     * @param mixed  $value
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
