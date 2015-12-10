<?php
/**
 * Class that include database functions and configuration
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */
namespace nightlinus\OracleDb;

use nightlinus\OracleDb\Utills\Alias;

/**
 * Class Database
 */
class Database
{

    /**
     * Profiler for db instance
     *
     * @type Profiler\Profiler
     */
    public $profiler;

    /**
     * Configuration storage
     *
     * @type Config
     */
    protected $configuration;

    /**
     * @type resource connection resource
     */
    protected $connection;

    /**
     * @type Driver\AbstractDriver
     */
    protected $driver;


    /**
     * @type \nightlinus\OracleDb\Session\Oracle
     */
    protected $session;

    /**
     * @type StatementCache
     */
    protected $statementCache;

    /**
     * Consttructor for Database class implements
     * base parametrs checking
     *
     * @param string|array $userName
     * @param string       $password
     * @param string       $connectionString
     *
     * @param array        $config
     *
     * @throws Exception
     */
    public function __construct(
        $userName,
        $password = null,
        $connectionString = null,
        $config = [ ]
    ) {

        if (func_num_args() === 1) {
            $config = $userName;
        } else {
            $config[ Config::CONNECTION_USER ] = $userName;
            $config[ Config::CONNECTION_PASSWORD ] = $password;
            $config[ Config::CONNECTION_STRING ] = $connectionString;
        }

        $this->configuration = new Config($config);
        $driver = $this->config(Config::DRIVER_CLASS);
        $this->driver = is_string($driver) ? new $driver() : $driver;
    }

    /**
     *  Освобождаем ресурсы в деструкторе
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * @param string $sqlText
     * @param int    $returnSize
     * @param null   $bindings
     * @param null   $mode
     *
     * @return mixed
     */
    public function call($sqlText, $returnSize = 4000, $bindings = null, $mode = null)
    {
        $return = null;
        $returnName = null;
        if ($returnSize) {
            $returnName = Alias::unique();
            $bindings[ (string) $returnName ] = [ null, $returnSize ];
            $return = ":$returnName := ";
        }
        $sqlText = "BEGIN $return $sqlText; END;";
        $statement = $this->query($sqlText, $bindings, $mode);

        return $returnSize ? $statement->out($returnName) : null;
    }

    /**
     * Commit session changes to server
     *
     * @throws Exception
     * @return $this
     */
    public function commit()
    {
        $this->driver->commit($this->connection);

        return $this;
    }

    /**
     * General function to get and set
     * configuration values
     *
     * @param string|array $name
     * @param null|mixed   $value
     *
     * @throws Exception
     * @return mixed
     */
    public function config($name, $value = null)
    {
        return $this->configuration->config(...func_get_args());
    }

    /**
     * Method to connect to database
     * It performs base connection checking
     * and client identifiers init.
     *
     * @return $this
     * @throws Exception
     */
    public function connect()
    {
        if ($this->connection) {
            return $this;
        }
        $this->setupBeforeConnect();
        $driver = $this->driver;
        if ($this->config(Config::CONNECTION_PERSISTENT)) {
            $connectMode = $driver::CONNECTION_TYPE_PERSISTENT;
        } elseif ($this->config(Config::CONNECTION_CACHE)) {
            $connectMode = $driver::CONNECTION_TYPE_CACHE;
        } else {
            $connectMode = $driver::CONNECTION_TYPE_NEW;
        }
        $this->connection = $driver->connect(
            $connectMode,
            $this->config(Config::CONNECTION_USER),
            $this->config(Config::CONNECTION_PASSWORD),
            $this->config(Config::CONNECTION_STRING),
            $this->config(Config::CONNECTION_CHARSET),
            $this->config(Config::CONNECTION_PRIVILEGED)
        );
        $this->session->apply();

        return $this;
    }

    /**
     * @param string     $sql
     * @param array|null $bindings
     *
     * @return int
     */
    public function count($sql, $bindings = null)
    {
        $statement = $this->prepare($sql);
        $statement->bind($bindings);

        return $statement->count();
    }

    /**
     * Method to stop measuring profile
     *
     * @return $this
     */
    public function endProfile()
    {
        if ($this->config(Config::PROFILER_ENABLED)) {
            $this->profiler->end();
        }

        return $this;
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $skip
     * @param int    $maxRows
     * @param int    $mode
     *
     * @return array
     */
    public function fetchAll($sql, $bindings = null, $skip = 0, $maxRows = -1, $mode = OCI_FETCHSTATEMENT_BY_COLUMN)
    {
        return $this->query($sql, $bindings)->fetchAll($skip, $maxRows, $mode);
    }

    /**
     * @param  string $sql
     * @param array   $bindings
     * @param int     $mode
     *
     * @return \array[]|\Generator
     */
    public function fetchArray($sql, $bindings = null, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchArray($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mode
     *
     * @return \array[]|\Generator
     */
    public function fetchAssoc($sql, $bindings = null, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchAssoc($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param null   $callback
     * @param int    $mode
     *
     * @return \Generator|mixed
     */
    public function fetchCallback($sql, $bindings = null, $callback = null, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchCallback($callback, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $index
     * @param int    $mode
     *
     * @return array|\Generator
     */
    public function fetchColumn($sql, $bindings = null, $index = 1, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchColumn($index, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mapIndex
     * @param int    $mode
     *
     * @return \array[]|\Generator
     * @throws \nightlinus\OracleDb\Exception
     */
    public function fetchMap($sql, $bindings = null, $mapIndex = 1, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchMap($mapIndex, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     *
     * @return \array[]|\Generator
     */
    public function fetchObject($sql, $bindings = null)
    {
        return $this->query($sql, $bindings)->fetchObject();
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mode
     *
     * @return \array[]
     */
    public function fetchOne($sql, $bindings = null, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchOne($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $firstCol
     * @param int    $secondCol
     *
     * @return array|\Generator
     * @throws \nightlinus\OracleDb\Exception
     */
    public function fetchPairs($sql, $bindings = null, $firstCol = 1, $secondCol = 2)
    {
        return $this->query($sql, $bindings)->fetchPairs($firstCol, $secondCol);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $index
     *
     * @return string
     * @throws \nightlinus\OracleDb\Exception
     */
    public function fetchValue($sql, $bindings = null, $index = 1)
    {
        return $this->query($sql, $bindings)->fetchValue($index);
    }

    /**
     * Function to access current connection
     *
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return Driver\AbstractDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Get oracle RDBMS version
     *
     * @return string
     * @throws Driver\Exception
     */
    public function getServerVersion()
    {
        $this->connect();
        $version = $this->driver->getServerVersion($this->connection);

        return $version;
    }

    /**
     * Methods to prepare Database statement
     * object from raw queryString
     *
     * @param string $sqlText
     *
     * @return Statement
     * @throws Driver\Exception
     */
    public function prepare($sqlText)
    {
        $this->connect();
        $statement = $this->getStatement($sqlText);
        $statement->prepare();

        return $statement;
    }

    /**
     * Shortcut method to prepare and fetch
     * statement.
     *
     * @param string     $sqlText
     * @param array|null $bindings
     * @param null       $mode
     *
     * @return Statement
     * @throws Exception
     */
    public function query($sqlText, $bindings = null, $mode = null)
    {
        $statement = $this->prepare($sqlText);
        $statement->bind($bindings);
        $statement->execute($mode);

        return $statement;
    }

    /**
     * Properly quote identifiers
     *
     * @param $variable
     *
     * @return string
     */
    public function quote($variable)
    {
        return $this->driver->quote($variable);
    }

    /**
     * Rollback changes within session
     *
     * @return $this
     * @throws Exception
     */
    public function rollback()
    {
        $this->driver->rollback($this->connection);

        return $this;
    }

    /**
     * Method for batch running «;» delimited queries
     *
     * @param $scriptText
     *
     * @throws Exception
     * @return $this
     */
    public function runScript($scriptText)
    {
        $queries = explode(';', $scriptText);
        $exceptions = [ ];
        $exceptionMessage = '';
        foreach ($queries as $query) {
            try {
                $query = trim($query);
                $len = strlen($query);
                if ($len > 0) {
                    $this->query($query);
                }
            } catch (\Exception $e) {
                $exceptions[] = $e;
                $exceptionMessage .= $e->getMessage() . PHP_EOL;
            }
        }

        if (count($exceptions)) {
            throw new Exception($exceptionMessage);
        }

        return $this;
    }

    /**
     * @param $profileId
     *
     * @return $this
     */
    public function startFetchProfile($profileId)
    {
        if ($this->config(Config::PROFILER_ENABLED)) {
            return $this->profiler->startFetch($profileId);
        }

        return null;
    }

    /**
     * @param $sql
     * @param $bindings
     *
     * @return $this
     */
    public function startProfile($sql, $bindings = null)
    {
        if ($this->config(Config::PROFILER_ENABLED)) {
            return $this->profiler->start($sql, $bindings);
        }

        return null;
    }

    /**
     * @param $profileId
     *
     * @return $this
     */
    public function stopFetchProfile($profileId)
    {
        if ($this->config(Config::PROFILER_ENABLED)) {
            return $this->profiler->stopFetch($profileId);
        }

        return null;
    }

    /**
     * Get current Oracle client version
     *
     * @return mixed
     */
    public function version()
    {
        return $this->driver->getClientVersion();
    }

    /**
     * Cleaning memory by dissposing connection
     * handlers
     *
     * @return $this
     * @throws Exception
     */
    protected function disconnect()
    {
        if (!$this->connection) {
            return $this;
        }
        $this->driver->disconnect($this->connection);

        return $this;
    }

    /**
     * @param $sql
     *
     * @return Statement
     * @throws Exception
     */
    protected function getStatement($sql)
    {
        $statementCacheEnabled = $this->config(Config::STATEMENT_CACHE_ENABLED);
        $statementCache = null;

        if ($statementCacheEnabled) {
            $statement = $this->statementCache->get($sql);
        } else {
            $statement = new Statement($sql, $this);
            $this->statementCache->add($statement);
        }

        return $statement;
    }

    /**
     * Method to set up connection before call of oci_connect
     *
     * @return $this
     * @throws Exception
     */
    protected function setupBeforeConnect()
    {
        //Set up profiler
        $class = $this->config(Config::SESSION_CLASS);
        $this->session = is_string($class) ? new $class($this) : $class;

        //Set up profiler
        if ($this->config(Config::PROFILER_ENABLED)) {
            $class = $this->config(Config::PROFILER_CLASS);
            $this->profiler = is_string($class) ? new $class() : $class;
        }

        //Set up cache
        if ($this->config(Config::STATEMENT_CACHE_ENABLED)) {
            $class = $this->config(Config::STATEMENT_CACHE_CLASS);
            $cacheSize = $this->config(Config::STATEMENT_CACHE_SIZE);
            $this->statementCache = is_string($class) ? new $class($cacheSize) : $class;
        }
        $this->session->setupBeforeConnect();
    }
}
