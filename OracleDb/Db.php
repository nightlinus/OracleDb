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
     * @var array
     */
    public static $OCI_SESSION_MODE;

    /**
     * @var Statement
     */
    public $lastStatement;

    /**
     * Profiler for db instance
     *
     * @var Profiler
     */
    public $profiler;

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

    /********************************************************************************
     * PSR-0 Autoloader
     *
     * Do not use if you are using Composer to autoload dependencies.
     *******************************************************************************/

    /**
     * Consttructor for Db class implements
     * base parametrs checking
     *
     * @param string        $userName
     * @param string        $password
     * @param string        $connectionString
     * @param Profiler|null $profiler
     *
     * @throws Exception
     */
    public function __construct(
        $userName,
        $password,
        $connectionString,
        $profiler = null
    ) {
        //Заполняем массив возможных значений session mode
        self::$OCI_SESSION_MODE = [
            OCI_DEFAULT,
            OCI_SYSOPER,
            OCI_SYSDBA,
            OCI_CRED_EXT,
            OCI_CRED_EXT + OCI_SYSDBA,
            OCI_CRED_EXT + OCI_SYSOPER
        ];

        if (!isset($userName) || !isset($password) || !isset($connectionString)) {
            throw new Exception("One of connection parameters is null or not set");
        }

        $this->config = $this->getDefaultSettings();

        $this->userName = $userName;
        $this->password = $password;
        $this->connectionString = $connectionString;
        $this->profiler = $profiler ? : new Profiler();
    }

    /**
     * OracleDb PSR-0 autoloader
     */
    public static function autoload($className)
    {
        $thisClass = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);

        $baseDir = __DIR__;

        if (substr($baseDir, -strlen($thisClass)) === $thisClass) {
            $baseDir = substr($baseDir, 0, -strlen($thisClass));
        }

        $className = ltrim($className, '\\');
        $fileName = $baseDir;
        if (($lastNsPos = strripos($className, '\\')) !== false) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (file_exists($fileName)) {
            /** @noinspection PhpIncludeInspection */
            require $fileName;
        }
    }

    /**
     * Register PSR-0 autoloader
     */
    public static function registerAutoloader()
    {
        spl_autoload_register(__NAMESPACE__ . "\\Db::autoload");
    }

    /**
     *  Освобождаем ресурсы в деструкторе
     */
    public function __destruct()
    {
        $this->disconnect();
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
     * Commit session changes to server
     *
     * @throws Exception
     * @return $this
     */
    public function commit()
    {
        $commitResult = oci_commit($this->connection);
        if (!$commitResult) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        return $this;
    }

    /**
     * Get current Oracle client version
     *
     * @return mixed
     */
    public function getClientVersion()
    {
        /** @noinspection PhpUndefinedFunctionInspection */
        return oci_client_version();
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
     * Function to access current connection
     *
     * @return resource
     */
    public function getConnection()
    {
        return $this->connection;
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
        if (!$version) {
            $error = $this->getOCIError();
            throw new Exception($error['message'], $error['code']);
        }

        return $version;
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
        $charset = $this->config('session.charset');
        $connectionMode = $this->config('connection.privileged');
        $prefetch = $this->config('connection.prefetch');
        if ($this->config('connection.persistent')) {
            $this->connection = oci_pconnect(
                $this->userName,
                $this->password,
                $this->connectionString,
                $charset,
                $connectionMode
            );
        } else {
            $this->connection = oci_connect(
                $this->userName,
                $this->password,
                $this->connectionString,
                $charset,
                $connectionMode
            );
        }
        if (!$this->connection) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        if ($prefetch !== false) {
          $this->setPrefetch($prefetch);
        }

        /** @noinspection PhpUndefinedFunctionInspection */
        oci_set_client_identifier($this->connection, $this->config('client.identifier'));
        /** @noinspection PhpUndefinedFunctionInspection */
        oci_set_client_info($this->connection, $this->config('client.info'));
        /** @noinspection PhpUndefinedFunctionInspection */
        oci_set_module_name($this->connection, $this->config('client.moduleName'));

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
        /*
         * Simple caching of last statement
         */
        if (isset($this->lastStatement) && $sqlText === $this->lastStatement->getQueryString()) {
            $statement = $this->lastStatement;
        } else {
            $statement = $this->prepare($sqlText);
        }

        $statement->bind($bindings);
        $statement->execute($mode);

        return $statement;
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
        $this->lastStatement = new Statement($this, $sqlText);
        $this->lastStatement->prepare();

        return $this->lastStatement;
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
        if (!$rollbackResult) {
            throw new Exception('Can not rollback');
        }

        return $this;
    }

    /**
     * Setter for lastStatement
     *
     * @see $lastStatement
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
            'connection.persistent' => false,
            'connection.privileged' => OCI_DEFAULT,
            'connection.prefetch'   => false,
            'client.identifier'     => '',
            'client.info'           => '',
            'client.moduleName'     => '',
            'profiler.enabled'      => false
        ];
    }

    /**
     * @param $sql
     * @param $bindings
     *
     * @return $this
     */
    public function startProfile($sql, $bindings)
    {
        if ($this->config('profiler.enabled')) {
            $this->profiler->start($sql, $bindings);
        }
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
        $exceptions = [];
        $exceptionMessage = '';
        foreach ($queries as $query) {
            try {
                $query = trim($query);
                $len = strlen($query);
                if ($len > 0) {
                    $this->query($query);
                }
            } catch (Exception $e) {
                $exceptions[] = $e;
                $exceptionMessage .= $e->getMessage() .PHP_EOL;
            }
        }

        if (count($exceptions)) {
            $e = new Exception();
            throw new Exception($exceptionMessage);
        }

        return $this;
    }
}
