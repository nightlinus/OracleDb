<?php
/**
 * Date: 11.09.14
 * Time: 12:32
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <user@localhost>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb;

/**
 * Class Config
 * It stores and manipilate client configuration
 * @package nightlinus\OracleDb
 */
class Config implements \ArrayAccess
{
    const CLIENT_IDENTIFIER = 'client.identifier';
    const CLIENT_INFO       = 'client.info';
    const CLIENT_MODULENAME = 'client.moduleName';

    const CONNECTION_CACHE      = 'connection.cache';
    const CONNECTION_CLASS      = 'connection.class';
    const CONNECTION_EDITION    = 'connection.edition';
    const CONNECTION_PERSISTENT = 'connection.persistent';
    const CONNECTION_PRIVILEGED = 'connection.privileged';

    const PROFILER_CLASS   = 'profiler.class';
    const PROFILER_ENABLED = 'profiler.enabled';

    const SESSION_CHARSET       = 'session.charset';
    const SESSION_CURRENTSCHEMA = 'session.currentSchema';
    const SESSION_DATEFORMAT    = 'session.dateFormat';
    const SESSION_DATELANGUAGE  = 'session.dateLanguage';

    const STATEMENT_AUTOCOMMIT    = 'statement.autocommit';
    const STATEMENT_CACHE_CLASS   = 'statement.cache.class';
    const STATEMENT_CACHE_ENABLED = 'statement.cache.enabled';
    const STATEMENT_CACHE_SIZE    = 'statement.cache.size';

    protected $config   = [ ];

    protected $defaults = [
        self::SESSION_CHARSET         => 'AL32UTF8',
        self::SESSION_DATEFORMAT      => 'DD.MM.YYYY HH24:MI:SS',
        self::SESSION_DATELANGUAGE    => false,
        self::SESSION_CURRENTSCHEMA   => false,
        self::CONNECTION_PERSISTENT   => false,
        self::CONNECTION_PRIVILEGED   => OCI_DEFAULT,
        self::CONNECTION_CACHE        => false,
        self::CONNECTION_CLASS        => false,
        self::CONNECTION_EDITION      => false,
        self::CLIENT_IDENTIFIER       => '',
        self::CLIENT_INFO             => '',
        self::CLIENT_MODULENAME       => '',
        self::PROFILER_ENABLED        => false,
        self::PROFILER_CLASS          => Profiler::class,
        self::STATEMENT_AUTOCOMMIT    => false,
        self::STATEMENT_CACHE_ENABLED => true,
        self::STATEMENT_CACHE_SIZE    => 50,
        self::STATEMENT_CACHE_CLASS   => StatementCache::class
    ];

    /**
     * @param string $key
     * @return mixed
     * @throws Exception
     */
    public function get($key)
    {
        if (isset($this->config[ $key ])) {
            $value = $this->config[ $key ];
        } elseif (isset($this->defaults[ $key ])) {
            $value = $this->defaults[ $key ];
        } else {
            throw new Exception();
        }

        return $value;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     */
    public function offsetExists($offset)
    {
        return isset($this->defaults[ $offset ]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->config[ $offset ]);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @throws Exception
     */
    public function set($key, $value)
    {
        if (isset($this->defaults[ $key ])) {
            $this->config[ $key ] = $value;
        } else {
            throw new Exception();
        }
    }
}
