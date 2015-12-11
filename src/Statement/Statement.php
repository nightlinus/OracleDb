<?php
/**
 * Class that include database statement functionality
 * this is wrapper above php oci extension.
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Statement;

use nightlinus\OracleDb\Config;
use nightlinus\OracleDb\Database;
use nightlinus\OracleDb\Driver\AbstractDriver;
use nightlinus\OracleDb\Driver\Exception;
use nightlinus\OracleDb\FieldDescription;
use nightlinus\OracleDb\Profiler\Profiler;

/**
 * Implements wrapper above oci8
 * php extension. Contains method
 * to execute and fetch data from
 * database statements
 */
class Statement implements \IteratorAggregate
{


    /**
     * Describe what fetch function shoud be used
     */
    const FETCH_ARRAY = 1;
    const FETCH_ASSOC = 2;
    const FETCH_BOTH  = 3;
    const FETCH_OBJ   = 4;

    const RETURN_ARRAY    = 1;
    const RETURN_ITERATOR = 0;

    /**
     * List of statement types
     */
    const TYPE_ALTER   = 'ALTER';
    const TYPE_BEGIN   = 'BEGIN';
    const TYPE_CALL    = 'CALL';
    const TYPE_CREATE  = 'CREATE';
    const TYPE_DECLARE = 'DECLARE';
    const TYPE_DELETE  = 'DELETE';
    const TYPE_DROP    = 'DROP';
    const TYPE_INSERT  = 'INSERT';
    const TYPE_SELECT  = 'SELECT';
    const TYPE_UNKNOWN = 'UNKNOWN';
    const TYPE_UPDATE  = 'UPDATE';

    /**
     * array that contains all
     * host-variable bindings
     *
     * @type array|null
     */
    private $bindings;

    /**
     * Instance of parent database object
     *
     * @type Database
     */
    private $db;

    /**
     * @type AbstractDriver
     */
    private $driver;

    /**
     * Index of profile associated with statement
     *
     * @type int
     */
    private $profileId;

    /**
     * Raw sql text, that was used
     * in oci_parse function
     *
     * @type  string
     */
    private $queryString;

    /**
     * Rsource of db statement
     *
     * @type resource
     */
    private $resource;

    /**
     * Flag to determine return type: array or iterator
     *
     * @type int
     */
    private $returnType;

    /**
     * @type StatementState
     */
    private $state;

    /**
     * @type Profiler
     */
    private $profiler;

    /**
     * @param string         $queryString sql выражение стейтмента в текстовом виде
     * @param Database       $db          ссылка на родительский объект базы данных
     * @param AbstractDriver $driver
     * @param Profiler       $profiler
     */
    public function __construct($queryString, Database $db, AbstractDriver $driver, Profiler $profiler)
    {
        $this->queryString = $queryString;
        $this->db = $db;
        $this->driver = $driver;
        $this->profiler = $profiler;
        $this->returnType = $this->db->config(Config::STATEMENT_RETURN_TYPE);
        $this->state = StatementState::freed();
    }

    /**
     * Class destructor called on script exit;
     */
    public function __destruct()
    {
        $this->free();
    }

    /**
     * Method to bind host-variables
     * Example:
     * $stmt->bind([
     *  ':host_var' => $value,
     *  ':host_var2' => $value2
     * ]);
     *
     * @param array $bindings array of host-variable names
     *                        and values.
     *
     * @return $this
     * @throws Exception
     */
    public function bind($bindings)
    {
        if (!is_array($bindings) || count($bindings) === 0) {
            return $this;
        }
        $this->bindings = [ ];
        $this->prepare();
        foreach ($bindings as $bindingName => $bindingValue) {
            $this->bindValue($bindingName, $bindingValue);
        }

        return $this;
    }

    /**
     * Method to bind array host-variables
     *
     * @param string $name      name of host variable
     * @param array  $binding   array to bind
     * @param int    $maxLength maximum length of array
     *
     * @param int    $maxItemLength
     * @param int    $type
     *
     * @throws Exception
     * @return $this
     */
    public function bindArray($name, $binding, $maxLength, $maxItemLength = null, $type = null)
    {
        $this->prepare();
        $this->driver->bindArray(
            $this->resource,
            $name,
            $binding,
            $maxLength,
            $maxItemLength,
            $type
        );

        $this->bindings[ $name ] = $binding;

        return $this;
    }

    /**
     * @param string|int $column
     * @param mixed      $variable
     * @param int        $type
     *
     * @return $this
     */
    public function bindColumn($column, &$variable, $type = null)
    {
        $this->driver->bindColumn($this->resource, $column, $variable, $type);

        return $this;
    }

    /**
     * @param $name
     * @param $value
     *
     * @return $this
     */
    public function bindValue($name, $value)
    {
        $driver = $this->driver;
        $hostVariable = HostVariable::with($value);
        $hostVariable = $this->transformHostVariable($hostVariable);
        $this->bindings[ $name ] = $hostVariable->value();
        $bindValue = &$this->bindings[ $name ];

        $driver->bindValue(
            $this->resource,
            $name,
            $bindValue,
            $hostVariable->length(),
            $hostVariable->type()
        );

        return $this;
    }

    /**
     * Whether statement can be realesed or not
     *
     * @return bool true if in any state besides fetching
     */
    public function canBeFreed()
    {
        return $this->state->isSafeToFree();
    }

    /**
     * Proxy to db commit method
     * Needed here for convinient method chaining.
     *
     * @return Database
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Method to get count of rows for SELECT and
     * count of affected rows from other stetement types
     *
     * @return int
     */
    public function count()
    {
        $type = $this->getType();
        if (self::TYPE_SELECT === $type && $this->state->isNotFetchedYet()) {
            $sql = "SELECT COUNT(*) FROM ({$this->queryString})";
            $count = $this->db->query($sql, $this->bindings)->fetchValue();
        } else {
            $count = $this->getAffectedRowsNumber();
        }

        return $count;
    }

    /**
     * Method to get full statement description for each field
     *
     * @return array[]
     */
    public function describe()
    {
        $fieldNmber = $this->getFieldNumber() + 1;
        $result = [ ];
        for ($i = 1; $i < $fieldNmber; $i++) {
            $result[] = $this->getFieldDescription($i);
        }

        return $result;
    }

    /**
     * Method to execute sql inside statement
     *
     * @param int|null $mode    this parameter is
     *                          powered by autocommit setting
     *
     * @return $this
     * @throws Exception
     */
    public function execute($mode = null)
    {
        $this->prepare();
        $driver = $this->driver;
        $mode = $this->getExecuteMode($mode);
        $this->profileId = $this->profiler->start($this->queryString, $this->bindings);
        $this->driver->execute($this->resource, $mode);
        $this->profiler->end();
        $this->state = $mode & $driver::EXECUTE_DESCRIBE ? $this->state->described() : $this->state->executed();

        return $this;
    }

    /**
     * @param $skip
     * @param $maxRows
     * @param $mode
     *
     * @return array
     */
    public function fetchAll($skip, $maxRows, $mode)
    {
        $result = $this->driver->fetchAll($this->resource, $skip, $maxRows, $mode);
        $this->state = $this->state->fetched();

        return $result;
    }

    /**
     * Fetch data as simple numeric keys array
     *
     * @param int $mode    constant that describe
     *                     type of fetched array:
     *                     with numeric keys or strings
     *                     or both OCI_ASSOC or OCI_ALL, OCI_NUM
     *
     * @return array[] | \Generator
     */
    public function fetchArray($mode = null)
    {
        return $this->getResultObject(null, self::FETCH_ARRAY, $mode);
    }

    /**
     * Fetch data as asscociative
     * array
     *
     * @param int $mode    constant that describe
     *                     type of fetched array:
     *                     with numeric keys or strings
     *                     or both OCI_ASSOC or OCI_ALL, OCI_NUM
     *
     * @return array[] | \Generator
     */
    public function fetchAssoc($mode = null)
    {
        return $this->getResultObject(null, self::FETCH_ASSOC, $mode);
    }

    /**
     * Fetch using custom callback
     *
     * @param callable $callback ($item, $index)
     * @param int      $mode
     *
     * @return \Generator|mixed
     */
    public function fetchCallback(callable $callback, $mode = null)
    {
        $fetchCallback = function (CallbackResult $result, $item, $index) use ($callback) {
            $cbResult = $callback($item, $index);

            if (count($cbResult) > 1) {
                $key = $index;
                $value = $cbResult;
            } else {
                $key = key($cbResult);
                $value = $cbResult[ $key ];
            }

            $result->key = $key;
            $result->value = $value;

            return $result;
        };

        return $this->getResultObject($fetchCallback, self::FETCH_ASSOC, $mode);
    }

    /**
     * Method for fetching data into 1
     * dimension array with values from
     * $column, index is numeric
     *
     * @param int|string $column set column to fetch from
     * @param int        $mode
     *
     * @return array | \Generator
     */
    public function fetchColumn($column = 1, $mode = null)
    {
        if (is_numeric($column)) {
            $fetchMode = self::FETCH_ARRAY;
            $column--;
        } else {
            $fetchMode = self::FETCH_ASSOC;
        }

        $callback = function (CallbackResult $result, $item, $index) use ($column) {
            $result->key = $index;
            $result->value = $item[ $column ];

            return $result;
        };

        return $this->getResultObject($callback, $fetchMode, $mode);
    }

    /**
     * @param int|string $mapIndex
     * @param int        $mode
     *
     * @throws Exception
     * @return \Generator|array[]
     */
    public function fetchMap($mapIndex = 1, $mode = null)
    {
        if (is_numeric($mapIndex)) {
            if ($mapIndex < 1) {
                throw new Exception("Column index start from 1, but «{$mapIndex}» was passed.");
            }
            $fetchMode = self::FETCH_ARRAY;
            $mapIndex--;
        } else {
            $fetchMode = self::FETCH_ASSOC;
        }

        $callback = function (CallbackResult $result, $item) use ($mapIndex) {
            $result->key = $item[ $mapIndex ];
            $result->value = $item;

            return $result;
        };

        return $this->getResultObject($callback, $fetchMode, $mode);
    }

    /**
     * Fetch data from statement to the php object
     *
     * @return array[] | \Generator
     */
    public function fetchObject()
    {
        return $this->getResultObject(null, self::FETCH_OBJ);
    }

    /**
     * Fetch only first row
     *
     * @param int $mode
     *
     * @return \array[]
     * @throws Exception
     */
    public function fetchOne($mode = null)
    {
        $result = $this->tupleGenerator(null, self::FETCH_BOTH, $mode)->current();
        $this->state = $this->state->fetched();

        return $result;
    }

    /**
     * Fetches data into key-values pair.
     * implemented as associative array
     * where keys are $firstCol values and
     * values are $secondCol values
     *
     * @param int|string $firstCol  колонка с ключом
     * @param int|string $secondCol колонка со значением
     *
     * @throws Exception
     * @return array | \Generator
     */
    public function fetchPairs($firstCol = 1, $secondCol = 2)
    {
        if (is_numeric($firstCol) && is_numeric($secondCol)) {
            if ($firstCol < 1 || $secondCol < 1) {
                throw new Exception("Column index start from 1, but «{$firstCol}», «{$secondCol}» were passed.");
            }
            $mode = self::FETCH_ARRAY;
            //make proper index to indicate that first column has index of 0
            $firstCol--;
            $secondCol--;
        } else {
            $mode = self::FETCH_ASSOC;
        }

        $callback = function (CallbackResult $result, $item) use ($firstCol, $secondCol) {
            $result->key = $item[ $firstCol ];
            $result->value = $item[ $secondCol ];

            return $result;
        };

        return $this->getResultObject($callback, $mode);
    }

    /**
     * Fetches single value from first row
     * and column specified by $index
     *
     * @param int|string $index number or string
     *                          that indicates column
     *                          to fetch value from
     *
     * @throws Exception
     * @return string
     */
    public function fetchValue($index = 1)
    {
        if (is_numeric($index)) {
            if ($index < 1) {
                throw new Exception("Column index start from 1, but «{$index}» was passed.");
            }
            $fetchMode = self::FETCH_ARRAY;
            //make proper index to indicate that first column has index of 0
            $index--;
        } else {
            $fetchMode = self::FETCH_ASSOC;
        }
        $result = $this->tupleGenerator(null, $fetchMode)->current()[ $index ];
        $this->state = $this->state->fetched();

        return $result;
    }

    /**
     * Method for free statement resource
     */
    public function free()
    {
        $this->state = $this->state->freed();
        if ($this->resource) {
            $this->driver->free($this->resource);
        }
    }

    /**
     * Get the number of rows affected by statement
     * as an integer
     *
     * @throws Exception
     * @return int
     */
    public function getAffectedRowsNumber()
    {
        return $this->driver->getAffectedRowsNumber($this->resource);
    }

    /**
     * Get description of data columns
     *
     * @param int $index
     *
     * @return FieldDescription
     * @trows \InvalidArgumentException
     */
    public function getFieldDescription($index)
    {
        $this->executeDescribe();
        $index = (int) $index;
        if ($index < 1) {
            throw new \InvalidArgumentException("Index must be larger then 1, given $index.");
        }

        return new FieldDescription(
            $this->driver->getFieldName($this->resource, $index),
            $this->driver->getFieldSize($this->resource, $index),
            $this->driver->getFieldPrecision($this->resource, $index),
            $this->driver->getFieldScale($this->resource, $index),
            $this->driver->getFieldType($this->resource, $index),
            $this->driver->getFieldTypeRaw($this->resource, $index)
        );
    }

    /**
     * Get number of columns in data
     *
     * @return int
     * @throws Exception
     */
    public function getFieldNumber()
    {
        $this->executeDescribe();
        $result = $this->driver->getFieldNumber($this->resource);

        return $result;
    }

    /**
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Iterator An instance of an object implementing <b>Iterator</b>
     */
    public function getIterator()
    {
        return $this->tupleGenerator();
    }

    /**
     * Getter for queryString
     *
     * @return string
     */
    public function getQueryString()
    {
        return $this->queryString;
    }

    /**
     * Get type of statement.
     * It can return any from:
     * SELECT UPDATE DELETE INSERT CREATE DROP ALTER BEGIN DECLARE CALL
     *
     * @throws Exception
     * @return string
     */
    public function getType()
    {
        $this->prepare();
        $type = $this->driver->getStatementType($this->resource);

        return $type;
    }

    /**
     * @param string $key
     *
     * @return array|null
     */
    public function out($key)
    {
        if (!isset($this->bindings[ $key ])) {
            throw new \InvalidArgumentException("There is no host variable set with name '$key'.");
        }

        return $this->bindings[ $key ];
    }

    /**
     * Method prepare oci8 statement for execute
     *
     * @return Statement $this
     * @throws Exception
     */
    public function prepare()
    {
        if ($this->state->isPrepared()) {
            return $this;
        }

        if ($this->queryString) {
            // get oci8 statement resource
            $this->resource = $this->driver->parse($this->db->getConnection(), $this->queryString);
        } else {
            // get new cursor handler if no query provided
            $this->resource = $this->driver->newCursor($this->db->getConnection());
        }
        $this->state = $this->state->prepared();

        return $this;
    }

    /**
     * Proxy to db rollback method.
     * Needed here for convinient method chaining.
     *
     * @return Database
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * Sets number of rows preloaded from database,
     * bigger rowCount leads to smallaer amount of network requests
     *
     * @param $rowCount
     *
     * @return $this
     * @throws Exception
     */
    public function setPrefetch($rowCount)
    {
        if ($this->resource) {
            $this->driver->setPrefcth($this->resource, $rowCount);
        }

        return $this;
    }

    public function returnArray()
    {
        $this->returnType  = self::RETURN_ARRAY;

        return $this;
    }

    public function retutnIterator()
    {
        $this->returnType = self::RETURN_ITERATOR;

        return $this;
    }

    /**
     * @param HostVariable $variable
     *
     * @return HostVariable
     */
    private function transformHostVariable(HostVariable $variable)
    {
        $driver = $this->driver;
        $value = $variable->value();
        if ($value instanceof Statement) {
            $value->prepare();
            $variable->with($value->resource, null, $driver::TYPE_CURSOR);
        } elseif (is_object($value) && $variable->type() === null) {
            $variable = $variable->with((string) $value);
        }

        return $variable;
    }

    /**
     * @return \nightlinus\OracleDb\Driver\AbstractDriver
     */
    private function executeDescribe()
    {
        $driver = $this->driver;
        $this->execute($driver::EXECUTE_DESCRIBE);

        return $this;
    }


    /**
     * If $mode not in oci constants list, then use db config value
     *
     * @param int $mode
     *
     * @return int
     */
    private function getExecuteMode($mode)
    {
        $driver = $this->driver;
        if (!$driver->isExecuteMode($mode)) {
            $mode = $this->db->config(
                Config::STATEMENT_AUTOCOMMIT
            ) ? $driver::EXECUTE_AUTO_COMMIT : $driver::EXECUTE_NO_AUTO_COMMIT;
        }

        return $mode;
    }

    /**
     * Return fetch function to retrieve data form database
     *
     * @param      $fetchMode
     *
     * @param null $mode
     *
     * @return callable|null
     */
    private function getFetchFunction($fetchMode, $mode = null)
    {
        $driver = $this->driver;
        $defaultMode = $driver::DEFAULT_FETCH_MODE;
        $mode = is_numeric($mode) ? $mode : $defaultMode;
        switch ($fetchMode) {
            case self::FETCH_BOTH:
                $fetchFunction = function () use ($mode) {
                    return $this->driver->fetch($this->resource, $mode);
                };
                break;
            case self::FETCH_ARRAY:
                $fetchFunction = function () use ($mode) {
                    return $this->driver->fetchArray($this->resource, $mode);
                };
                break;
            case self::FETCH_ASSOC:
                $fetchFunction = function () use ($mode) {
                    return $this->driver->fetchAssoc($this->resource, $mode);
                };
                break;
            case self::FETCH_OBJ:
                $fetchFunction = function () {
                    return $this->driver->fetchObject($this->resource);
                };
                break;
            default:
                $fetchFunction = function () use ($defaultMode) {
                    return $this->driver->fetchAssoc($this->resource, $defaultMode);
                };
        }

        if ($this->profileId) {
            $fetchFunction = function () use ($fetchFunction) {
                $this->profiler->startFetch($this->profileId);
                $res = $fetchFunction();
                $this->profiler->stopFetch($this->profileId);

                return $res;
            };
        }

        return $fetchFunction;
    }

    /**
     * Returns array or iterator depending on return type
     *
     * @param $callback
     * @param $fetchMode
     * @param $mode
     *
     * @return \Generator|mixed
     * @throws Exception
     */
    private function getResultObject($callback, $fetchMode, $mode = null)
    {
        $result = $this->tupleGenerator($callback, $fetchMode, $mode);
        if (self::RETURN_ITERATOR !== $this->returnType) {
            $result = iterator_to_array($result, true);
        }

        return $result;
    }

    /**
     * Generator for iterating over fetched rows
     *
     * @param callable|null $callback
     *
     * @param int           $fetchMode
     * @param null          $mode
     *
     * @throws Exception
     * @return \Generator
     */
    private function tupleGenerator($callback = null, $fetchMode = null, $mode = null)
    {
        if (!$this->state->isFetchable()) {
            $this->execute();
        }

        $this->state = $this->state->fetching();
        $fetchFunction = $this->getFetchFunction($fetchMode, $mode);
        if (!is_callable($callback)) {
            $callback = function (CallbackResult $result, $item, $index) {
                $result->key = $index;
                $result->value = $item;

                return $result;
            };
        }

        $result = new CallbackResult();
        for ($index = 0; false !== ($tuple = $fetchFunction()); $index++) {
            $callback($result, $tuple, $index);
            yield $result->key => $result->value;
        }

        $this->state = $this->state->fetched();
    }
}