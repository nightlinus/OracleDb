<?php
/**
 * Class that include databese statement functionality
 * this is wrapper above php oci extension.
 *
 * PHP version 5.5
 *
 * @category Database
 * @package  OracleDb
 * @author   Ogarkov Mikhail <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  GIT: 1
 * @link     http://github.com
 */
namespace OracleDb;

/**
 * Class OracleDb
 * @package Oracle
 */
class Db
{
    /**
     * Profiler for db instance
     *
     * @var Profiler
     */
    public $profiler;


    /**
     * @var StatementCache
     */
    protected $statementCache;

    /**
     * @var resource connection resource
     */
    protected $connection;

    /**
     * @var string
     */
    protected $connectionString;

    /**
     * @var string password for db connection
     */
    protected $password;


    /**
     * @var string username for db connection
     */
    protected $userName;

    /**
     * Array with settings key => value pair
     *
     * @var array
     */
    protected $config;


    /**
     * last executed statement
     *
     * @var Statement | null
     */
    protected $lastStatement;

    /**
     * Consttructor for Db class implements
     * base parametrs checking
     *
     * @param string $userName
     * @param string $password
     * @param string $connectionString
     *
     * @throws Exception
     *
     */
    public function __construct(
        $userName,
        $password,
        $connectionString
    )
    {
        if (!isset($userName) || !isset($password) || !isset($connectionString)) {
            throw new Exception("One of connection parameters is null or not set");
        }
        $this->config = $this->getDefaultSettings();
        $this->userName = $userName;
        $this->password = $password;
        $this->connectionString = $connectionString;
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
        $returnName = "r___" . sha1(microtime(true));
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
        $commitResult = oci_commit($this->connection);
        if ($commitResult === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

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
                $intersect = array_intersect_key($this->config, $name);
                if (count($intersect) !== count($name)) {
                    $diff = array_diff_key($name, $intersect);
                    $diff = implode(',', $diff);
                    throw new Exception("Not all config entries are valid: $diff");
                }
                $this->config = array_merge($this->config, $name);
            } else {
                if (isset($this->config[ $name ])) {
                    return $this->config[ $name ];
                } else {
                    throw new Exception("No such config entry: $name");
                }
            }
        } else {
            $this->config[ $name ] = $value;
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
        if ($this->config('connection.persistent')) {
            $connectFunction = 'oci_pconnect';
        } else {
            $connectFunction = $this->config('connection.cache') ? 'oci_connect' : 'oci_new_connect';
        }
        $this->connection = $connectFunction(
            $this->userName,
            $this->password,
            $this->connectionString,
            $this->config('session.charset'),
            $this->config('connection.privileged')
        );
        if ($this->connection === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }
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
        if ($this->config('profiler.enabled')) {
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
     * @throws Exception
     */
    public function getServerVersion()
    {
        $this->connect();
        $version = oci_server_version($this->connection);
        if ($version === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        return $version;
    }

    /**
     * Methods to prepare Db statement
     * object from raw queryString
     *
     * @param string $sqlText
     *
     * @return Statement
     * @throws Exception
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
     * Rollback changes within session
     *
     * @return $this
     * @throws Exception
     */
    public function rollback()
    {
        $rollbackResult = oci_rollback($this->connection);
        if ($rollbackResult === false) {
            throw new Exception('Can not rollback');
        }

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
            } catch (Exception $e) {
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
        if ($this->config('profiler.enabled')) {
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
        if ($this->config('profiler.enabled')) {
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
        if ($this->config('profiler.enabled')) {
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
        /** @noinspection PhpUndefinedFunctionInspection */
        return oci_client_version();
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
        if (!oci_close($this->connection)) {
            throw new Exception("Can't close connection");
        }

        return $this;
    }

    /**
     * Method to fetch OCI8 error
     * Returns associative array with
     * "code" and "message" keys.
     *
     * @return array
     */
    protected function getOCIError()
    {
        $ociConnection = $this->connection;

        return is_resource($ociConnection) ?
            oci_error($ociConnection) :
            oci_error();
    }

    /**
     * Method returns default settings
     * for Db class
     *
     * @return array
     */
    protected function getDefaultSettings()
    {
        return [
            'session.charset'       => 'AL32UTF8',
            'session.autocommit'    => false,
            'session.dateFormat'    => 'DD.MM.YYYY HH24:MI:SS',
            'session.dateLanguage'  => false,
            'connection.persistent' => false,
            'connection.privileged' => OCI_DEFAULT,
            'connection.cache'      => false,
            'connection.class'      => false,
            'connection.edition'    => false,
            'client.identifier'     => '',
            'client.info'           => '',
            'client.moduleName'     => '',
            'profiler.enabled'      => false,
            'profiler.class'        => __NAMESPACE__.'\\Profiler',
            'statement.cache'       => true,
            'statement.cache.size'  => 50,
            'statement.cache.class' => __NAMESPACE__.'\\StatementCache'
        ];
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
     * Setup session after connection is estabilished
     *
     * @return $this
     */
    protected function setUpSessionAfter()
    {
        //Set up profiler
        if ($this->config('profiler.enabled')) {
            $class = $this->config('profiler.class');
            $this->profiler = is_string($class) ? new $class() : $class;
        }

        //Set up cache
        if ($this->config('statement.cache')) {
            $class = $this->config('statement.cache.class');
            $cacheSize = $this->config('statement.cache.size');
            $this->statementCache = is_string($class) ? new $class($cacheSize) : $class;
        }

        /** @noinspection PhpUndefinedFunctionInspection */
        oci_set_client_identifier($this->connection, $this->config('client.identifier'));
        /** @noinspection PhpUndefinedFunctionInspection */
        oci_set_client_info($this->connection, $this->config('client.info'));
        /** @noinspection PhpUndefinedFunctionInspection */
        oci_set_module_name($this->connection, $this->config('client.moduleName'));
        $setUp = [ ];
        if ($this->config('session.dateFormat')) {
            $setUp[ 'NLS_DATE_FORMAT' ] = $this->config('session.dateFormat');
        }
        if ($this->config('session.dateLanguage')) {
            $setUp[ 'NLS_DATE_LANGUAGE' ] = $this->config('session.dateLanguage');
        }
        $this->alterSession($setUp);

        return $this;
    }

    /**
     * Method to set up connection before call of oci_connect
     *
     * @return $this
     * @throws Exception
     */
    private function setUpSessionBefore()
    {
        $connectionClass = $this->config('connection.class');
        if ($connectionClass) {
            ini_set('oci8.connection_class', $connectionClass);
        }
        $edition = $this->config('connection.edition');
        if ($edition) {
            /** @noinspection PhpUndefinedFunctionInspection */
            $result = oci_set_edition($edition);
            if ($result === false) {
                throw new Exception("Edition setup failed: $edition");
            }
        }

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
        $statementCacheEnabled = $this->config('statement.cache');
        $statement = null;

        if ($statementCacheEnabled) {
            $statement = $this->statementCache->get($sql);
        }

        $statement = $statement ? : new Statement($this, $sql);

        if ($statementCacheEnabled) {
            $trashStatements = $this->statementCache->add($statement);
            /**
             * @var Statement $trashStatement
             */
            foreach ($this->statementCache as $trashStatement) {
                if ($trashStatement->canBeFreed()) {
                    $trashStatement->free();
                    if (--$trashStatements) {
                       break;
                   }
                }
            }

        }

        return $statement;
    }
}
