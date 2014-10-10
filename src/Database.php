<?php
/**
 * Class that include database functions and configuration
 *
 * PHP version 5.5
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   Ogarkov Mikhail <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  0.1.0
 * @link     https://github.com/nightlinus/OracleDb
 */
namespace nightlinus\OracleDb;

/**
 * Class Database
 *
 * @package nightlinus\OracleDb
 */
class Database
{

    /**
     * Profiler for db instance
     *
     * @type Profiler
     */
    public $profiler;

    /**
     * Configuration storage
     *
     * @type Config
     */
    protected $config;

    /**
     * @type resource connection resource
     */
    protected $connection;

    /**
     * @type Driver\DriverInterface
     */
    protected $driver;

    /**
     * last executed statement
     *
     * @type Statement | null
     */
    protected $lastStatement;

    /**
     * @type StatementCache
     */
    protected $statementCache;

    /**
     * Consttructor for Database class implements
     * base parametrs checking
     *
     * @param string $userName
     * @param string $password
     * @param string $connectionString
     *
     * @param array  $config
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

        $this->config = new Config($config);
        $this->config->validate();
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
        $returnName = $this->getUniqueAlias('z__');
        $bindings[ $returnName ] = [ null, $returnSize ];
        $sqlText = "BEGIN :$returnName := $sqlText; END;";
        $statement = $this->query($sqlText, $bindings, $mode);

        return $statement->bindings[ $returnName ];
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
     * @param string     $name
     * @param null|mixed $value
     *
     * @throws Exception
     * @return mixed
     */
    public function config($name, $value = null)
    {
        if (func_num_args() === 1) {
            if (is_array($name)) {
                $this->config->set($name);
            } else {
                return $this->config->get($name);
            }
        } else {
            $this->config->set($name, $value);
        }

        return $value;
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
        $this->setUpSessionBefore();
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
        $this->setUpSessionAfter();

        return $this;
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
     * Function to access current connection
     *
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return Driver\DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return Statement
     */
    public function getLastStatement()
    {
        return $this->lastStatement;
    }

    /**
     * Setter for lastStatement
     *
     * @see $lastStatement
     *
     * @param $statement
     *
     * @return $this
     */
    public function setLastStatement($statement)
    {
        $this->lastStatement = $statement;

        return $this;
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
     * @param array|null &$bindings
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
        $queries = explode(";", $scriptText);
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
                $exceptions[ ] = $e;
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
     * Method to set session variables via ALTER SESSION SET variable = value
     *
     * @param array $variables
     *
     * @return $this
     */
    protected function alterSession($variables)
    {
        if (count($variables) === 0) {
            return $this;
        }
        $sql = "ALTER SESSION SET ";
        foreach ($variables as $key => $value) {
            $sql .= "$key = '$value' ";
        }
        $this->query($sql);

        return $this;
    }

    /**
     * Gather session information from config
     *
     * @return array
     */
    protected function collectSessionSettings()
    {
        $setUp = [ ];
        if ($this->config(Config::SESSION_DATE_FORMAT)) {
            $setUp[ 'NLS_DATE_FORMAT' ] = $this->config(Config::SESSION_DATE_FORMAT);
        }
        if ($this->config(Config::SESSION_DATE_LANGUAGE)) {
            $setUp[ 'NLS_DATE_LANGUAGE' ] = $this->config(Config::SESSION_DATE_LANGUAGE);
        }
        if ($this->config(Config::SESSION_CURRENT_SCHEMA)) {
            $setUp[ 'CURRENT_SCHEMA' ] = $this->config(Config::SESSION_CURRENT_SCHEMA);
        }

        return $setUp;
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
            $statementCache = $this->statementCache->get($sql);
        }

        $statement = $statementCache ?: new Statement($this, $sql);

        if ($statementCacheEnabled && $statementCache === null) {
            $trashStatements = $this->statementCache->add($statement);
            $iter = $this->statementCache->getIterator();
            while ($trashStatements) {
                /**
                 * @type Statement $trashStatement
                 */
                $trashStatement = $iter->current();
                if ($trashStatement->canBeFreed()) {
                    $trashStatement->free();
                    if (--$trashStatements) {
                        break;
                    }
                }
                $iter->next();
            }
        }

        return $statement;
    }

    /**
     * Generate unique alias for naming
     * host variables or aliases
     *
     * @param string $prefix
     *
     * @return string
     */
    protected function getUniqueAlias($prefix)
    {
        $hash = uniqid($prefix, true);
        $hash = str_replace('.', '', $hash);

        return $hash;
    }

    /**
     * Setup session after connection is estabilished
     *
     * @return $this
     */
    protected function setUpSessionAfter()
    {
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

        $this->driver->setClientIdentifier($this->connection, $this->config(Config::CLIENT_IDENTIFIER));
        $this->driver->setClientInfo($this->connection, $this->config(Config::CLIENT_INFO));
        $this->driver->setClientModuleName($this->connection, $this->config(Config::CLIENT_MODULE_NAME));

        $this->alterSession($this->collectSessionSettings());

        return $this;
    }

    /**
     * Method to set up connection before call of oci_connect
     *
     * @return $this
     * @throws Exception
     */
    protected function setUpSessionBefore()
    {
        $connectionClass = $this->config(Config::CONNECTION_CLASS);
        if ($connectionClass) {
            ini_set('oci8.connection_class', $connectionClass);
        }
        $edition = $this->config(Config::CONNECTION_EDITION);
        if ($edition) {
            $this->driver->setEdition($edition);
        }

        return $this;
    }
}
