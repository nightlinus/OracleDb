<?php /** @noinspection MoreThanThreeArgumentsInspection */

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

use Generator;
use nightlinus\OracleDb\Driver\AbstractDriver;
use nightlinus\OracleDb\Statement\HostVariable;
use nightlinus\OracleDb\Statement\Statement;
use nightlinus\OracleDb\Statement\StatementFactory;
use nightlinus\OracleDb\Utills\Alias;
use function is_string;
use const PHP_EOL;

class Database
{
    /**
     * Configuration storage
     *
     * @var Config
     */
    private $configuration;

    /**
     * @var resource connection resource
     */
    private $connection;

    /**
     * @var Driver\AbstractDriver
     */
    private $driver;

    /**
     * @var \nightlinus\OracleDb\Session\Oracle
     */
    private $session;

    /**
     * @var StatementFactory
     */
    private $statementFactory;

    public function __construct(
        StatementFactory $statementFactory,
        Config $config,
        AbstractDriver $driver
    ) {
        $this->configuration = $config;
        $this->driver = $driver;
        $this->statementFactory = $statementFactory;
    }

    /**
     * Освобождаем ресурсы в деструкторе
     *
     * @throws Driver\Exception
     */
    public function __destruct()
    {
        $this->statementFactory = null;
        $this->disconnect();
    }

    /**
     * @param string $sqlText
     * @param int    $returnSize
     * @param null   $bindings
     * @param null   $mode
     *
     * @return mixed
     * @throws Exception
     * @throws Driver\Exception
     */
    public function call($sqlText, $returnSize = 4000, array $bindings = [], $mode = null)
    {
        $return = null;
        $returnName = null;
        if ($returnSize) {
            $returnName = Alias::unique();
            $bindings[ (string) $returnName ] = HostVariable::with(null, $returnSize);
            $return = ":$returnName := ";
        }
        $sqlText = "BEGIN $return $sqlText; END;";
        $statement = $this->query($sqlText, $bindings, $mode);

        return $returnSize ? $statement->out($returnName) : null;
    }

    /**
     * Commit session changes to server
     *
     * @return $this
     * @throws Driver\Exception
     */
    public function commit(): self
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
     * @throws Driver\Exception
     */
    public function connect(): self
    {
        if ($this->connection) {
            return $this;
        }
        $this->setupBeforeConnect();
        $driver = $this->driver;
        if ($this->configuration->get(Config::CONNECTION_PERSISTENT)) {
            $connectMode = $driver::CONNECTION_TYPE_PERSISTENT;
        } elseif ($this->configuration->get(Config::CONNECTION_CACHE)) {
            $connectMode = $driver::CONNECTION_TYPE_CACHE;
        } else {
            $connectMode = $driver::CONNECTION_TYPE_NEW;
        }
        $this->connection = $driver->connect(
            $connectMode,
            $this->configuration->get(Config::CONNECTION_USER),
            $this->configuration->get(Config::CONNECTION_PASSWORD),
            $this->configuration->get(Config::CONNECTION_STRING),
            $this->configuration->get(Config::CONNECTION_CHARSET),
            $this->configuration->get(Config::CONNECTION_PRIVILEGED)
        );
        $this->setupAfterConnect();

        return $this;
    }

    /**
     * @param string     $sql
     * @param array|null $bindings
     *
     * @return int
     * @throws Driver\Exception
     * @throws Exception
     */
    public function count($sql, array $bindings = [])
    {
        $statement = $this->prepare($sql);
        $statement->bind($bindings);

        return $statement->count();
    }


    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $skip
     * @param int    $maxRows
     * @param int    $mode
     *
     * @return array
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchAll($sql, array $bindings = [], $skip = 0, $maxRows = -1, $mode = OCI_FETCHSTATEMENT_BY_COLUMN)
    {
        return $this->query($sql, $bindings)->fetchAll($skip, $maxRows, $mode);
    }

    /**
     * @param  string $sql
     * @param array   $bindings
     * @param int     $mode
     *
     * @return iterable|iterable[]
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchArray($sql, array $bindings = [], $mode = null): iterable
    {
        return $this->query($sql, $bindings)->fetchArray($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mode
     *
     * @return iterable|iterable[]
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchAssoc($sql, array $bindings = [], $mode = null): iterable
    {
        return $this->query($sql, $bindings)->fetchAssoc($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param null   $callback
     * @param int    $mode
     *
     * @return Generator|mixed
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchCallback($sql, array $bindings = [], $callback = null, $mode = null)
    {
        return $this->query($sql, $bindings)->fetchCallback($callback, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $index
     * @param int    $mode
     *
     * @return iterable
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchColumn($sql, array $bindings = [], $index = 1, $mode = null): iterable
    {
        return $this->query($sql, $bindings)->fetchColumn($index, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mapIndex
     * @param int    $mode
     *
     * @return iterable|iterable[]
     * @throws Driver\Exception
     * @throws Exception
     */
    public function fetchMap($sql, array $bindings = [], $mapIndex = 1, $mode = null): iterable
    {
        return $this->query($sql, $bindings)->fetchMap($mapIndex, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     *
     * @return iterable
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchObject($sql, array $bindings = []): iterable
    {
        return $this->query($sql, $bindings)->fetchObject();
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mode
     *
     * @return array
     * @throws Exception
     * @throws Driver\Exception
     */
    public function fetchOne($sql, array $bindings = [], $mode = null): array
    {
        return $this->query($sql, $bindings)->fetchOne($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $firstCol
     * @param int    $secondCol
     *
     * @return iterable
     * @throws \nightlinus\OracleDb\Exception
     * @throws Driver\Exception
     */
    public function fetchPairs($sql, array $bindings = [], $firstCol = 1, $secondCol = 2): iterable
    {
        return $this->query($sql, $bindings)->fetchPairs($firstCol, $secondCol);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $index
     *
     * @return string|null
     * @throws \nightlinus\OracleDb\Exception
     * @throws Driver\Exception
     */
    public function fetchValue($sql, array $bindings = [], $index = 1): ?string
    {
        return $this->query($sql, $bindings)->fetchValue($index);
    }

    /**
     * @param  string $sql
     * @param array   $bindings
     * @param int     $mode
     *
     * @return iterable|iterable[]
     * @throws Exception
     * @throws Driver\Exception
     */
    public function yieldArray($sql, array $bindings = [], $mode = null): iterable
    {
        return $this->queryGenerator($sql, $bindings)->fetchArray($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mode
     *
     * @return iterable|iterable[]
     * @throws Exception
     * @throws Driver\Exception
     */
    public function yieldAssoc($sql, array $bindings = [], $mode = null): iterable
    {
        return $this->queryGenerator($sql, $bindings)->fetchAssoc($mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $index
     * @param int    $mode
     *
     * @return iterable
     * @throws Exception
     * @throws Driver\Exception
     */
    public function yieldColumn($sql, array $bindings = [], $index = 1, $mode = null): iterable
    {
        return $this->queryGenerator($sql, $bindings)->fetchColumn($index, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $firstCol
     * @param int    $secondCol
     *
     * @return iterable
     * @throws \nightlinus\OracleDb\Exception
     * @throws Driver\Exception
     */
    public function yieldPairs($sql, array $bindings = [], $firstCol = 1, $secondCol = 2): iterable
    {
        return $this->queryGenerator($sql, $bindings)->fetchPairs($firstCol, $secondCol);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param int    $mapIndex
     * @param int    $mode
     *
     * @return iterable|iterable[]
     * @throws Driver\Exception
     * @throws Exception
     */
    public function yieldMap($sql, array $bindings = [], $mapIndex = 1, $mode = null): iterable
    {
        return $this->queryGenerator($sql, $bindings)->fetchMap($mapIndex, $mode);
    }

    /**
     * @param string $sql
     * @param array  $bindings
     * @param null   $callback
     * @param int    $mode
     *
     * @return iterable
     * @throws Exception
     * @throws Driver\Exception
     */
    public function yieldCallback($sql, array $bindings = [], $callback = null, $mode = null): iterable
    {
        return $this->queryGenerator($sql, $bindings)->fetchCallback($callback, $mode);
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
    public function getDriver(): AbstractDriver
    {
        return $this->driver;
    }

    /**
     * Get oracle RDBMS version
     *
     * @return string
     * @throws Driver\Exception
     * @throws Exception
     */
    public function getServerVersion(): string
    {
        $this->connect();

        return $this->driver->getServerVersion($this->connection);
    }

    /**
     * Methods to prepare Database statement
     * object from raw queryString
     *
     * @param string $sqlText
     *
     * @return Statement
     * @throws Driver\Exception
     * @throws Exception
     */
    public function prepare($sqlText): Statement
    {
        $this->connect();
        $statement = $this->statementFactory->make($sqlText, $this);
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
     * @throws Driver\Exception
     */
    public function query(string $sqlText, array $bindings = [], $mode = null): Statement
    {
        $statement = $this->prepare($sqlText);
        $statement->bind($bindings);
        $statement->execute($mode);

        return $statement;
    }

    /**
     * Shortcut method to prepare and fetch
     * generator statement.
     *
     * @param string     $sqlText
     * @param array|null $bindings
     * @param null       $mode
     *
     * @return Statement
     * @throws Exception
     * @throws Driver\Exception
     */
    private function queryGenerator(string $sqlText, array $bindings = [], $mode = null): Statement
    {
        $statement = $this->prepare($sqlText);
        $statement->retutnIterator();
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
     * @throws Driver\Exception
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
        $exceptions = [];
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
     * Get current Oracle client version
     *
     * @return string
     */
    public function version(): string
    {
        return $this->driver->getClientVersion();
    }

    /**
     * Cleaning memory by dissposing connection
     * handlers
     *
     * @return $this
     * @throws Driver\Exception
     */
    private function disconnect()
    {
        if (!$this->connection) {
            return $this;
        }
        $this->driver->disconnect($this->connection);

        return $this;
    }

    /**
     * @throws Exception
     */
    private function setupBeforeConnect(): void
    {
        $class = $this->config(Config::SESSION_CLASS);
        $this->session = is_string($class) ? new $class($this->getDriver(), $this->configuration) : $class;
        $this->session->setupBeforeConnect();
    }

    /**
     * @throws Driver\Exception
     * @throws Exception
     */
    private function setupAfterConnect(): void
    {
        $sql = $this->session->apply($this->getConnection());
        $statement = $this->query($sql);
        $statement->free();
    }
}
