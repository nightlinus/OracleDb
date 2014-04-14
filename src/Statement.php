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
 * Implements wrapper above oci8
 * php extension. Contains method
 * to execute and fetch data from
 * database statements
 *
 * @package OracleDb
 */
class Statement implements \IteratorAggregate
{

    const TYPE_ALTER = 'ALTER';

    const TYPE_BEGIN = 'BEGIN';

    const TYPE_CALL = 'CALL';

    const TYPE_CREATE = 'CREATE';

    const TYPE_DECLARE = 'DECLARE';

    const TYPE_DELETE = 'DELETE';

    const TYPE_DROP = 'DROP';

    const TYPE_INSERT = 'INSERT';

    const TYPE_SELECT = 'SELECT';

    const TYPE_UPDATE = 'UPDATE';

    const TYPE_UNKNOWN = 'UNKNOWN';

    const STATE_FREED = 0;

    const STATE_PREPARED = 1;

    const STATE_FETCHED = 2;

    const STATE_EXECUTED_DESCRIBE = 4;

    const STATE_EXECUTED = 8;

    const FETCH_ARRAY = 1;

    const FETCH_ASSOC = 2;

    const FETCH_OBJ = 4;

    const FETCH_ALL = 8;

    /**
     * Internal state of Statement
     *
     * @var int
     */
    protected $state;

    /**
     * result of last fetch fucntion
     *
     * @var array[]
     */
    public $result;

    /**
     * array that contains all
     * host-variable bindings
     *
     * @var array|null
     */
    public $bindings;

    /**
     * Default fetch function used
     * to get data from statement
     * Its wrapper above oci_fetch_array, oci_fetch_object
     *
     * @var callable
     */
    protected $defaultFetchFunction;

    /**
     * Rsource of db statement
     *
     * @var resource
     */
    protected $resource;

    /**
     * Raw sql text, that was used
     * in oci_parse function
     *
     * @var  string
     */
    protected $queryString;

    /**
     * Instance of parent database object
     *
     * @var Db
     */
    protected $db;

    /**
     * Index of profile associated with statement
     *
     * @var int
     */
    protected $profileId;

    /**
     * В конструкторе, кроме инициализации ресурсов,
     * определяем обработчик выборки по умолчанию.
     *
     * @param Db     $db          ссылка на родительский объект базы данных
     * @param string $queryString sql выражение стейтмента в текстовом виде
     *
     * @throws Exception
     */
    public function __construct(Db $db, $queryString)
    {
        if ($queryString === '' || $queryString === null) {
            throw new Exception("SqlText is empty.");
        }
        $this->queryString = $queryString;
        $this->db = $db;
        $this->defaultFetchFunction = function () {
            return oci_fetch_array($this->resource, OCI_ASSOC);
        };
    }

    /**
     * Class destructor called on script exit;
     */
    public function __destruct()
    {
        $this->free();
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
                $value = isset($bindingValue[ 0 ]) ? $bindingValue[ 0 ] : (isset($bindingValue['value']) ? $bindingValue['value'] : null);
                $length = isset($bindingValue[ 1 ]) ? $bindingValue[ 1 ] : (isset($bindingValue[ 'length' ]) ? $bindingValue[ 'length' ] : $length);
                $type = isset($bindingValue[ 2 ]) ? $bindingValue[ 2 ] : (isset($bindingValue[ 'type' ]) ? $bindingValue[ 'type' ] : $type);
            }
            $this->bindings[$bindingName] = $value;
            $bindResult = oci_bind_by_name(
                $this->resource,
                $bindingName,
                $this->bindings[ $bindingName ],
                $length,
                $type
            );
            if ($bindResult === false) {
                $error = $this->getOCIError();
                throw new Exception($error[ 'message' ], $error[ 'code' ]);
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
     * @param        $maxItemLength
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
        if ($bindResult === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        $this->bindings[ $name ] = $binding;

        return $this;
    }

    /**
     * Proxy to db commit method
     * Needed here for convinient method chaining.
     *
     * @return Db
     */
    public function commit()
    {
        return $this->db->commit();
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
        if (array_search($mode,[OCI_NO_AUTO_COMMIT, OCI_COMMIT_ON_SUCCESS, OCI_DESCRIBE_ONLY]) === false) {
            $mode = $this->db->config('session.autocommit') ? OCI_COMMIT_ON_SUCCESS : OCI_NO_AUTO_COMMIT;
        }

        $this->profileId = $this->db->startProfile($this->queryString, $this->bindings);
        $executeResult = oci_execute($this->resource, $mode);
        $this->db->endProfile();
        $this->db->setLastStatement($this);

        if ($executeResult === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }
        if ($mode | OCI_DESCRIBE_ONLY) {
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
     * @return array[]
     */
    public function fetchArray($mode = OCI_NUM)
    {
        $fetchFunction = function () use ($mode) {
            return oci_fetch_array($this->resource, $mode);
        };

        return $this->iterateTuples($fetchFunction);
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
     * @return array[]
     */
    public function fetchAssoc($mode = OCI_ASSOC)
    {
        return $this->fetchArray($mode);
    }

    /**
     * Method for fetching data into 1
     * dimension array with values from
     * $column, index is numeric
     *
     * @param int|string $column set column to fetch from
     *
     * @return array
     */
    public function fetchColumn($column = 0)
    {
        if (is_numeric($column)) {
            $mode = OCI_NUM;
        } else {
            $mode = OCI_ASSOC;
        }

        $fetchFunction = function () use ($mode) {
            return oci_fetch_array($this->resource, $mode);
        };

        /** @noinspection PhpUnusedParameterInspection */
        $callback = function ($item, $index, &$result) use ($column) {
           return $result[ ] = $item[ $column ];
        };

        return $this->iterateTuples($fetchFunction, $callback);
    }

    /**
     * Method for fetching data as map
     */
    public function fetchMap()
    {
        throw new Exception("Not implemented yet");
    }

    /**
     * Fetch data from statement to the php object
     *
     * @return array[]
     */
    public function fetchObject()
    {
        $fetchFunction = function () {
            return oci_fetch_object($this->resource);
        };

        return $this->iterateTuples($fetchFunction);
    }

    /**
     * Fetches single value from first row
     * and column specified by $index
     *
     * @param int|string $index number or string
     *                          that indicates column
     *                          to fetch value from
     *
     * @return string
     */
    public function fetchOne($index = 1)
    {
        if (is_numeric($index)) {
            $mode = OCI_NUM;
            //make proper index to indicate that first column has index of 0
            $index--;
        } else {
            $mode = OCI_ASSOC;
        }


        $fetchFunction = function () use ($mode) {
            return oci_fetch_array($this->resource, $mode);
        };
        $this->result = $this->tupleGenerator($fetchFunction)->current()[ $index ];
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
     * @return array
     */
    public function fetchPairs($firstCol = 1, $secondCol = 2)
    {
        if (is_numeric($firstCol) && is_numeric($secondCol)) {
            $mode = OCI_NUM;
            //make proper index to indicate that first column has index of 0
            $firstCol--;
            $secondCol--;
        } else {
            $mode = OCI_ASSOC;
        }

        $fetchFunction = function () use ($mode) {
            return oci_fetch_array($this->resource, $mode);
        };

        /** @noinspection PhpUnusedParameterInspection */
        $callback = function ($item, $index, &$result) use ($firstCol, $secondCol) {
           return $result[ $item[ $firstCol ] ] = $item[ $secondCol ];
        };

        return $this->iterateTuples($fetchFunction, $callback);
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
        if ($rows === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        return $rows;
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
        $type = oci_statement_type($this->resource);
        if ($type === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        return $type;
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

        // get oci8 statement resource
        $this->resource = oci_parse($this->db->getConnection(), $this->queryString);

        if ($this->resource === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        $this->state = self::STATE_PREPARED;

        return $this;
    }

    /**
     * Proxy to db rollback method.
     * Needed here for convinient method chaining.
     *
     * @return Db
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * @param $rowCount
     *
     * @return $this
     * @throws Exception
     */
    public function setPrefetch($rowCount)
    {
        if ($this->resource) {
            $setResult = oci_set_prefetch($this->resource, $rowCount);
            if ($setResult === false) {
                $error = $this->getOCIError();
                throw new Exception($error[ 'message' ], $error[ 'code' ]);
            }
        }

        return $this;
    }

    /**
     * Itereate over all rows in fetched data
     *
     * @param callable $fetchFunction
     *
     * @param callable $callback Функция для обработки элементов выборки
     *                           Передаются параметры $item, $index, &result
     *
     * @return mixed
     */
    protected function iterateTuples($fetchFunction = null, $callback = null)
    {
        $this->result = [ ];
        $index = 0;
        if (!is_callable($callback)) {
            /** @noinspection PhpUnusedParameterInspection */
            $callback = function ($item, $index, &$result) {
                return $result[ ] = $item;
            };
        }
        foreach ($this->tupleGenerator($fetchFunction) as $tuple) {
          $res =  $callback($tuple, $index++, $this->result);
        }

        return $this->result;
    }

    /**
     * Generator for iterating over fetched rows
     *
     * @param callable|null $fetchFunction
     *
     * @throws Exception
     * @return \Generator
     */
    protected function tupleGenerator($fetchFunction = null)
    {
        if ($this->state === self::STATE_FETCHED) {
            throw new Exception("Statement is already fetched. Need to execute it before fetching again.");
        }

        if (!$this->isFetchable()) {
            $this->execute();
        }
        $profiledFetchFunction = null;
        $notProfiledFetchFunction = $fetchFunction ? : $this->defaultFetchFunction;
        if ($this->profileId) {
            $profiledFetchFunction = function() use ($notProfiledFetchFunction) {
                $this->db->startFetchProfile($this->profileId);
                $res = $notProfiledFetchFunction();
                $this->db->stopFetchProfile($this->profileId);
                return $res;
            };
        }
        $fetchFunction = $profiledFetchFunction ? : $notProfiledFetchFunction;

        while (($tuple = $fetchFunction()) !== false) {
            yield $tuple;
        }

        $this->state = self::STATE_FETCHED;
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
     * Method to get count of rows for SELECT and
     * count of affected rows from other stetement types
     *
     * @return int
     */
    public function count()
    {
        $type = $this->getType();
        if ($type === self::TYPE_SELECT && $this->state !== self::STATE_FETCHED) {
            $sql = "SELECT COUNT(*) FROM ({$this->queryString})";
            $prevStatement = $this->db->getLastStatement();
            $count = $this->db->query($sql, $this->bindings)->fetchOne();
            $this->db->setLastStatement($prevStatement);
        } else {
            $count = $this->getAffectedRowsNumber();
        }
        return $count;
    }

    /**
     * @param $index
     *
     * @return array
     * @throws Exception
     */
    public function getFieldMetadata($index)
    {
        if ($this->state < self::STATE_EXECUTED_DESCRIBE) {
            $this->execute(OCI_DESCRIBE_ONLY);
        }
        if (is_numeric($index) && $index < 1) {
            throw new Exception("Index must be larger then 1, index: $index");
        }
        $result = [
            'name'      => oci_field_name($this->resource, $index),
            'size'      => oci_field_size($this->resource, $index),
            'precision' => oci_field_precision($this->resource, $index),
            'scale'     => oci_field_scale($this->resource, $index),
            'type'      => oci_field_type($this->resource, $index),
            'typeDriver'   => oci_field_type_raw($this->resource, $index)
        ];

        foreach ($result as $field) {
            if ($field === false) {
                $error = $this->getOCIError();
                throw new Exception($error[ 'message' ], $error[ 'code' ]);
            }
        }

        return $result;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getFieldNumber()
    {
        if ($this->state < self::STATE_EXECUTED_DESCRIBE) {
            $this->execute(OCI_DESCRIBE_ONLY);
        }

        $result = oci_num_fields($this->resource);
        if ($result === false) {
            $error = $this->getOCIError();
            throw new Exception($error[ 'message' ], $error[ 'code' ]);
        }

        return $result;
    }

    /**
     * Method to get full statement metadata for each field
     *
     * @return array[]
     */
    public function getMetadata()
    {
        $fieldNmber = $this->getFieldNumber() + 1;
        $result = [ ];
        for ($i = 1; $i < $fieldNmber; $i++) {
            $result[ ] = $this->getFieldMetadata($i);
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function isFetchable()
    {
        return $this->state === self::STATE_EXECUTED ? true : false;
    }

    /**
     * @param      $fetchMode
     *
     * @param null $ociMode
     *
     * @return callable|null
     */
    protected function getFetchFunction($fetchMode, $ociMode = null)
    {
        $fetchFunction = null;
        switch ($fetchMode) {
            case self::FETCH_ARRAY:
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
                    $result = [];
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
}
