<?php
/**
 * Date: 07.10.14
 * Time: 18:56
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb\Driver;


/**
 * Class A
 *
 * @package nightlinus\OracleDb\Driver
 */
abstract class AbstractDriver
{
    /**
     *  CACHE = Use existing connection if was started with oci_conect
     *  NEW = Always open new connection
     *  PERSISTENT = Open persistent connection
     */
    const CONNECTION_TYPE_CACHE      = 0x02;
    const CONNECTION_TYPE_NEW        = 0x03;
    const CONNECTION_TYPE_PERSISTENT = 0x01;


    const DEFAULT_FETCH_MODE = 0x00;


    const EXECUTE_AUTO_COMMIT    = 0x02;
    const EXECUTE_DESCRIBE       = 0x01;
    const EXECUTE_NO_AUTO_COMMIT = 0x03;


    const RETURN_LOBS_AS_STRING = 0x02;
    const RETURN_NULLS          = 0x01;


    const TYPE_CURSOR = 0x01;

    /**
     * @param resource $handle
     * @param string   $name
     * @param mixed    $variable
     * @param int      $tableLength
     * @param int      $itemLength
     * @param int      $type
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
     abstract public function bindArray($handle, $name, &$variable, $tableLength, $itemLength = -1, $type = SQLT_AFC);

    /**
     * @param resource   $handle
     * @param int|string $column
     * @param mixed      $variable
     * @param int        $type
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function bindColumn($handle, $column, &$variable, $type = SQLT_CHR);

    /**
     * @param resource $handle
     * @param string   $name
     * @param mixed    $variable
     * @param int      $length
     * @param int      $type
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function bindValue($handle, $name, &$variable, $length = -1, $type = SQLT_CHR);

    /**
     * @param resource $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function commit($handle);

    /**
     * @param int    $connectionType
     * @param string $user
     * @param string $password
     * @param string $connectionString
     * @param string $charSet
     * @param int    $sessionMode
     *
     * @return resource
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function connect($connectionType, $user, $password, $connectionString, $charSet, $sessionMode);

    /**
     * @param resource $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function disconnect($handle);

    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function execute($handle, $mode);

    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return array
     */
    abstract public function fetch($handle, $mode);

    /**
     * @param resource $handle
     * @param int      $skip
     * @param int      $maxrows
     * @param int      $mode
     *
     * @return array
     */
    abstract public function fetchAll($handle, $skip, $maxrows, $mode);

    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return array
     */
    abstract public function fetchArray($handle, $mode);

    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return array
     */
    abstract public function fetchAssoc($handle, $mode);

    /**
     * @param resource $handle
     *
     * @return object
     */
    abstract public function fetchObject($handle);

    /**
     * @param resource $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function free(&$handle);

    /**
     * @param resource $handle
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getAffectedRowsNumber($handle);

    /**
     * @return string
     */
    abstract public function getClientVersion();

    /**
     * @param resource $handle
     *
     * @return array
     */
    abstract public function getError($handle = null);

    /**
     * @param resource   $handle statement resource
     * @param int|string $index  1 based index or name
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldName($handle, $index);

    /**
     * @param resource $handle statement resource
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldNumber($handle);

    /**
     * @param resource   $handle statement resource
     * @param int|string $index  1 based index or name
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldPrecision($handle, $index);

    /**
     * @param resource   $handle statement resource
     * @param int|string $index  1 based index or name
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldScale($handle, $index);

    /**
     * @param resource   $handle statement resource
     * @param int|string $index  1 based index or name
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldSize($handle, $index);

    /**
     * @param resource   $handle statement resource
     * @param int|string $index  1 based index or name
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldType($handle, $index);

    /**
     * @param resource   $handle statement resource
     * @param int|string $index  1 based index or name
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getFieldTypeRaw($handle, $index);

    /**
     * @param resource $handle
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getServerVersion($handle);

    /**
     * @param resource $handle
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function getStatementType($handle);

    /**
     * @param int $mode
     *
     * @return bool
     */
    abstract public function isExecuteMode($mode);

    /**
     * @param resource $handle
     *
     * @return resource
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function newCursor($handle);

    /**
     * @param resource $handle
     * @param string   $query
     *
     * @return resource
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function parse($handle, $query);

    /**
     * @param $variable
     *
     * @return string
     */
    abstract public function quote($variable);

    /**
     * Rollback changes within session
     *
     * @param resource $handle
     *
     * @throws \nightlinus\OracleDb\Driver\Exception
     * @return $this
     */
    abstract public function rollback($handle);

    /**
     * @param resource $handle
     * @param string   $identifier
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function setClientIdentifier($handle, $identifier);

    /**
     * @param resource $handle
     * @param string   $identifier
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function setClientInfo($handle, $identifier);

    /**
     * @param resource $handle
     * @param string   $identifier
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function setClientModuleName($handle, $identifier);

    /**
     * @param string $edition
     *              ]
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function setEdition($edition);

    /**
     * @param resource $handle
     * @param int      $size
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    abstract public function setPrefcth($handle, $size);
}
