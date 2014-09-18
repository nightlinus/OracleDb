<?php
/**
 * Class that include database statement functionality
 * this is wrapper above php oci extension.
 *
 * PHP version 5.5
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
 * Implements wrapper above oci8
 * php extension. Contains method
 * to execute and fetch data from
 * database statements
 *
 * @package OracleDb
 */
class Statement implements \IteratorAggregate
{

    /**
     * Describe what fetch function shoud be used
     */
    const FETCH_ALL   = 8;
    const FETCH_ARRAY = 1;
    const FETCH_ASSOC = 2;
    const FETCH_OBJ   = 4;

    const RETURN_ARRAY    = 1;
    const RETURN_ITERATOR = 0;

    /**
     * List of statement states
     */
    const STATE_EXECUTED          = 8;
    const STATE_EXECUTED_DESCRIBE = 4;
    const STATE_FETCHED           = 2;
    const STATE_FETCHING          = 16;
    const STATE_FREED             = 0;
    const STATE_PREPARED          = 1;

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
     * result of last fetch fucntion
     *
     * @type array[]
     */
    public $result;

    /**
     * array that contains all
     * host-variable bindings
     *
     * @type array|null
     */
    public $bindings;

    /**
     * Instance of parent database object
     *
     * @type Database
     */
    protected $db;

    /**
     * Index of profile associated with statement
     *
     * @type int
     */
    protected $profileId;

    /**
     * Raw sql text, that was used
     * in oci_parse function
     *
     * @type  string
     */
    protected $queryString;

    /**
     * Rsource of db statement
     *
     * @type resource
     */
    protected $resource;

    /**
     * Flag to determine return type: array or iterator
     *
     * @type int
     */
    protected $returnType;

    /**
     * Internal state of Statement
     *
     * @type int
     */
    protected $state;

    /**
     * В конструкторе, кроме инициализации ресурсов,
     * определяем обработчик выборки по умолчанию.
     *
     * @param Database $db          ссылка на родительский объект базы данных
     * @param string   $queryString sql выражение стейтмента в текстовом виде
     */
    public function __construct(Database $db, $queryString = null)
    {
        $this->state = self::STATE_FREED;
        $this->queryString = $queryString;
        $this->db = $db;
        $this->returnType = self::RETURN_ARRAY;
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
        $this->prepare();

        if (!is_array($bindings)) {
            return $this;
        }
        foreach ($bindings as $bindingName => $bindingValue) {
            $type = SQLT_CHR;
            $length = -1;
            $value = $bindingValue;
            if (is_array($bindingValue)) {
                $value = isset($bindingValue[ 0 ]) ?
                    $bindingValue[ 0 ] : (isset($bindingValue[ 'value' ]) ? $bindingValue[ 'value' ] : null);
                $length = isset($bindingValue[ 1 ]) ?
                    $bindingValue[ 1 ] : (isset($bindingValue[ 'length' ]) ? $bindingValue[ 'length' ] : $length);
                $type = isset($bindingValue[ 2 ]) ?
                    $bindingValue[ 2 ] : (isset($bindingValue[ 'type' ]) ? $bindingValue[ 'type' ] : $type);
            }

            $this->bindings[ $bindingName ] = $value;

            if ($value instanceof $this) {
                $type = OCI_B_CURSOR;
                $value->prepare();
                $bindValue = &$this->bindings[ $bindingName ]->resource;
            } else {
                $bindValue = &$this->bindings[ $bindingName ];
            }
            $bindResult = oci_bind_by_name(
                $this->resource,
                $bindingName,
                $bindValue,
                $length,
                $type
            );
            if (false === $bindResult) {
                $error = $this->getOCIError();
                throw new Exception($error);
            }
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
    public function bindArray($name, $binding, $maxLength, $maxItemLength = -1, $type = SQLT_AFC)
    {
        $this->prepare();
        $bindResult = oci_bind_array_by_name(
            $this->resource,
            $name,
            $binding,
            $maxLength,
            $maxItemLength,
            $type
        );
        if (false === $bindResult) {
            $error = $this->getOCIError();
            throw new Exception($error);
        }

        $this->bindings[ $name ] = $binding;

        return $this;
    }

    /**
     * Whether statement can be realesed or not
     *
     * @return bool true if in any state besides fetching
     */
    public function canBeFreed()
    {
        return $this->state < self::STATE_FETCHING;
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
        if (self::TYPE_SELECT === $type && self::STATE_FETCHED !== $this->state) {
            $sql = "SELECT COUNT(*) FROM ({$this->queryString})";
            $prevStatement = $this->db->getLastStatement();
            $count = $this->db->query($sql, $this->bindings)->fetchValue();
            $this->db->setLastStatement($prevStatement);
        } else {
            $count = $this->getAffectedRowsNumber();
        }

        return $count;
    }

    /**
     * Method to execute sql inside statement
     *
     * @param int|null $mode this parameter is
     *                       powered by autocommit setting
     *
     * @return $this
     * @throws Exception
     */
    public function execute($mode = null)
    {
        $this->prepare();

        //If $mode not in oci constants list, then use db config value
        if (array_search($mode, [ OCI_NO_AUTO_COMMIT, OCI_COMMIT_ON_SUCCESS, OCI_DESCRIBE_ONLY ], true) === false) {
            $mode = $this->db->config(Config::STATEMENT_AUTOCOMMIT) ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
        }

        $this->profileId = $this->db->startProfile($this->queryString, $this->bindings);
        $executeResult = oci_execute($this->resource, $mode);
        $this->db->endProfile();
        $this->db->setLastStatement($this);

        if ($executeResult === false) {
            $error = $this->getOCIError();
            throw new Exception($error);
        }
        if ($mode & OCI_DESCRIBE_ONLY) {
            $this->state = self::STATE_EXECUTED_DESCRIBE;
        } else {
            $this->state = self::STATE_EXECUTED;
        }

        return $this;
    }

    /**
     * Fetch data as simple numeric keys array
     *
     * @param int $mode constant that describe
     *                  type of fetched array:
     *                  with numeric keys or strings
     *                  or both OCI_ASSOC or OCI_ALL, OCI_NUM
     *
     * @return array[] | \Generator
     */
    public function fetchArray($mode = OCI_RETURN_NULLS)
    {
        if (($mode & OCI_NUM) === 0) {
            $mode = OCI_NUM + $mode;
        }

        return $this->getResultObject(null, self::FETCH_ARRAY, $mode);
    }

    /**
     * Fetch data as asscociative
     * array
     *
     * @param int $mode constant that describe
     *                  type of fetched array:
     *                  with numeric keys or strings
     *                  or both OCI_ASSOC or OCI_ALL, OCI_NUM
     *
     * @return array[] | \Generator
     */
    public function fetchAssoc($mode = OCI_RETURN_NULLS)
    {
        if (($mode & OCI_ASSOC) === 0) {
            $mode = OCI_ASSOC + $mode;
        }

        return $this->fetchArray($mode);
    }

    /**
     * Method for fetching data into 1
     * dimension array with values from
     * $column, index is numeric
     *
     * @param int|string $column set column to fetch from
     * @param int        $ociMode
     *
     * @return array | \Generator
     */
    public function fetchColumn($column = 1, $ociMode = OCI_RETURN_NULLS)
    {
        if (is_numeric($column)) {
            if (($ociMode & OCI_NUM) === 0) {
                $ociMode = OCI_NUM + $ociMode;
            }
            $column--;
        } else {
            if (($ociMode & OCI_ASSOC) === 0) {
                $ociMode = OCI_ASSOC + $ociMode;
            }
        }

        $callback = function ($item, $index) use ($column) {
            $result[ $index ] = $item[ $column ];

            return $result;
        };

        return $this->getResultObject($callback, self::FETCH_ARRAY, $ociMode);
    }

    /**
     * @param int|string $mapIndex
     * @param int        $ociMode
     *
     * @throws Exception
     * @return \Generator|array[]
     */
    public function fetchMap($mapIndex = 1, $ociMode = OCI_RETURN_NULLS)
    {
        if (is_numeric($mapIndex)) {
            if ($mapIndex < 1) {
                throw new Exception("Column index start from 1, but «{$mapIndex}» was passed.");
            }
            if (($ociMode & OCI_NUM) === 0) {
                $ociMode = OCI_NUM + $ociMode;
            }
            $mapIndex--;
        } else {
            if (($ociMode & OCI_ASSOC) === 0) {
                $ociMode = OCI_ASSOC + $ociMode;
            }
        }

        $callback = function ($item) use ($mapIndex) {
            $key = $item[ $mapIndex ];
            $result[ $key ] = $item;

            return $result;
        };

        return $this->getResultObject($callback, self::FETCH_ARRAY, $ociMode);
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
    public function fetchOne($mode = OCI_RETURN_NULLS)
    {
        if (($mode & OCI_ASSOC) === 0 && ($mode & OCI_NUM) === 0) {
            $mode = OCI_ASSOC + $mode;
        }

        $this->result = $this->tupleGenerator(null, self::FETCH_ARRAY, $mode)->current();
        $this->state = self::STATE_FETCHED;

        return $this->result;
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
            $mode = OCI_NUM + OCI_RETURN_NULLS;
            //make proper index to indicate that first column has index of 0
            $firstCol--;
            $secondCol--;
        } else {
            $mode = OCI_ASSOC + OCI_RETURN_NULLS;
        }

        $callback = function ($item) use ($firstCol, $secondCol) {
            $index = $item[ $firstCol ];
            $result[ $index ] = $item[ $secondCol ];

            return $result;
        };

        return $this->getResultObject($callback, self::FETCH_ARRAY, $mode);
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
            $mode = OCI_NUM + OCI_RETURN_NULLS;
            //make proper index to indicate that first column has index of 0
            $index--;
        } else {
            $mode = OCI_ASSOC + OCI_RETURN_NULLS;
        }

        $this->result = $this->tupleGenerator(null, self::FETCH_ARRAY, $mode)->current()[ $index ];
        $this->state = self::STATE_FETCHED;

        return $this->result;
    }

    /**
     * Method for free statement resource
     */
    public function free()
    {
        $this->state = self::STATE_FREED;
        if ($this->resource) {
            oci_free_statement($this->resource);
            $this->resource = null;
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
        $rows = oci_num_rows($this->resource);
        if (false === $rows) {
            $error = $this->getOCIError();
            throw new Exception($error);
        }

        return $rows;
    }

    /**
     * Get description of data columns
     *
     * @param $index
     *
     * @return array
     * @throws Exception
     */
    public function getFieldDescription($index)
    {
        if ($this->state < self::STATE_EXECUTED_DESCRIBE) {
            $this->execute(OCI_DESCRIBE_ONLY);
        }
        if (is_numeric($index) && $index < 1) {
            throw new Exception("Index must be larger then 1, index «{$index}».");
        }
        $result = [
            'name'       => oci_field_name($this->resource, $index),
            'size'       => oci_field_size($this->resource, $index),
            'precision'  => oci_field_precision($this->resource, $index),
            'scale'      => oci_field_scale($this->resource, $index),
            'type'       => oci_field_type($this->resource, $index),
            'typeRaw'    => oci_field_type_raw($this->resource, $index)
        ];

        foreach ($result as $field) {
            if (false === $field) {
                $error = $this->getOCIError();
                throw new Exception($error);
            }
        }

        return $result;
    }

    /**
     * Get number of columns in data
     *
     * @return int
     * @throws Exception
     */
    public function getFieldNumber()
    {
        if ($this->state < self::STATE_EXECUTED_DESCRIBE) {
            $this->execute(OCI_DESCRIBE_ONLY);
        }

        $result = oci_num_fields($this->resource);
        if (false === $result) {
            $error = $this->getOCIError();
            throw new Exception($error);
        }

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
     * Method to get full statement description for each field
     *
     * @return array[]
     */
    public function describe()
    {
        $fieldNmber = $this->getFieldNumber() + 1;
        $result = [ ];
        for ($i = 1; $i < $fieldNmber; $i++) {
            $result[ ] = $this->getFieldDescription($i);
        }

        return $result;
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
        $type = oci_statement_type($this->resource);
        if (false === $type) {
            $error = $this->getOCIError();
            throw new Exception($error);
        }

        return $type;
    }

    /**
     * True if statement can be fetched, false otherwise
     *
     * @return bool
     */
    public function isFetchable()
    {
        return self::STATE_EXECUTED === $this->state ? true : false;
    }

    /**
     * Method prepare oci8 statement for execute
     *
     * @return Statement $this
     * @throws Exception
     */
    public function prepare()
    {
        if ($this->state >= self::STATE_PREPARED) {
            return $this;
        }

        if ($this->queryString) {
            // get oci8 statement resource
            $this->resource = oci_parse($this->db->getConnection(), $this->queryString);
        } else {
            // get new cursor handler if no query provided
            $this->resource = oci_new_cursor($this->db->getConnection());
        }

        if (false === $this->resource) {
            $error = $this->getOCIError();
            throw new Exception($error);
        }

        $this->state = self::STATE_PREPARED;

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
            $setResult = oci_set_prefetch($this->resource, $rowCount);
            if (false === $setResult) {
                $error = $this->getOCIError();
                throw new Exception($error);
            }
        }

        return $this;
    }

    /**
     * Sets return type: array or iteartor
     *
     * @param int $returnType
     *
     * @return $this
     */
    public function setReturnType($returnType)
    {
        $this->returnType = $returnType;

        return $this;
    }

    /**
     * Itereate over all rows in fetched data
     *
     * @param callable $callback Функция для обработки элементов выборки
     *                           Передаются параметры $item, $index, &result
     *
     * @param int      $fetchMode
     *
     * @param int|null $ociMode
     *
     * @throws Exception
     * @return mixed
     */
    protected function aggregateTupples($callback = null, $fetchMode = null, $ociMode = null)
    {
        foreach ($this->tupleGenerator($callback, $fetchMode, $ociMode) as $key => $tuple) {
            $this->result[ $key ] = $tuple;
        }

        return $this->result;
    }

    /**
     * Return fetch function to retrieve data form database
     *
     * @param      $fetchMode
     *
     * @param null $ociMode
     *
     * @return callable|null
     */
    protected function getFetchFunction($fetchMode, $ociMode = null)
    {
        $ociMode = $ociMode ?: OCI_ASSOC + OCI_RETURN_NULLS;
        switch ($fetchMode) {
            case self::FETCH_ARRAY:
            case self::FETCH_ASSOC:
                $fetchFunction = function () use ($ociMode) {
                    return oci_fetch_array($this->resource, $ociMode);
                };
                break;
            case self::FETCH_OBJ:
                $fetchFunction = function () {
                    return oci_fetch_object($this->resource);
                };
                break;
            case self::FETCH_ALL:
                $fetchFunction = function () use ($ociMode) {
                    $result = [ ];
                    oci_fetch_all($this->resource, $result, null, $ociMode);

                    return $result;
                };
                break;
            default:
                $fetchFunction = function () {
                    return oci_fetch_array($this->resource, OCI_ASSOC + OCI_RETURN_NULLS);
                };
        }

        if ($this->profileId) {
            $fetchFunction = function () use ($fetchFunction) {
                $this->db->startFetchProfile($this->profileId);
                $res = $fetchFunction();
                $this->db->stopFetchProfile($this->profileId);

                return $res;
            };
        }

        return $fetchFunction;
    }

    /**
     * Fetches oci error for statement
     *
     * @return array
     */
    protected function getOCIError()
    {
        $ociResource = $this->resource;

        return is_resource($ociResource) ?
            oci_error($ociResource) :
            oci_error();
    }

    /**
     * Returns array or iterator depending on return type
     *
     * @param $callback
     * @param $fetchMode
     * @param $ociMode
     *
     * @return \Generator|mixed
     * @throws Exception
     */
    protected function getResultObject($callback, $fetchMode, $ociMode = null)
    {
        if (self::RETURN_ITERATOR === $this->returnType) {
            return $this->tupleGenerator($callback, $fetchMode, $ociMode);
        } else {
            return $this->aggregateTupples($callback, $fetchMode, $ociMode);
        }
    }

    /**
     * Generator for iterating over fetched rows
     *
     * @param      $callback
     *
     * @param int  $fetchMode
     * @param null $ociMode
     *
     * @throws Exception
     * @return \Generator
     */
    protected function tupleGenerator($callback = null, $fetchMode = null, $ociMode = null)
    {
        if (self::STATE_FETCHED === $this->state) {
            throw new Exception("Statement is already fetched. Need to execute it before fetching again.");
        }

        if (!$this->isFetchable()) {
            $this->execute();
        }
        $this->state = self::STATE_FETCHING;

        $fetchFunction = $this->getFetchFunction($fetchMode, $ociMode);
        $this->result = [ ];

        if (!is_callable($callback)) {
            $callback = function ($item, $index) {
                $result[ $index ] = $item;

                return $result;
            };
        }
        $index = 0;
        while (false !== ($tuple = $fetchFunction())) {
            $result = $callback($tuple, $index++);
            $key = key($result);
            yield $key => $result[ $key ];
        }

        $this->state = self::STATE_FETCHED;
    }
}
