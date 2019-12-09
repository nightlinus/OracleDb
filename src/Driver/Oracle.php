<?php /** @noinspection MoreThanThreeArgumentsInspection */

/**
 * Date: 07.10.14
 * Time: 12:50
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Driver;

use stdClass;
use function in_array;
use function is_array;
use function is_resource;
use function is_string;
use function mb_substr;
use function oci_set_action;
use function oci_set_edition;
use function oci_set_module_name;
use function oci_set_prefetch;

/**
 * Class Oracle
 */
class Oracle extends AbstractDriver
{
    public const DEFAULT_FETCH_MODE = OCI_RETURN_NULLS;

    public const EXECUTE_AUTO_COMMIT = OCI_COMMIT_ON_SUCCESS;
    public const EXECUTE_DESCRIBE = OCI_DESCRIBE_ONLY;
    public const EXECUTE_NO_AUTO_COMMIT = OCI_NO_AUTO_COMMIT;

    public const RETURN_LOBS_AS_STRING = OCI_RETURN_LOBS;
    public const RETURN_NULLS = OCI_RETURN_NULLS;

    public const TYPE_CURSOR = OCI_B_CURSOR;

    /**
     * @var array
     */
    protected $executeModes = [ OCI_NO_AUTO_COMMIT, OCI_COMMIT_ON_SUCCESS, OCI_DESCRIBE_ONLY ];

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
    public function bindArray($handle, $name, &$variable, $tableLength, $itemLength = -1, $type = SQLT_AFC)
    {
        if (null === $itemLength) {
            $itemLength = -1;
        }
        if (null === $type) {
            $type = SQLT_AFC;
        }
        $result = @oci_bind_array_by_name($handle, $name, $variable, $tableLength, $itemLength, $type);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }


    /**
     * @param resource   $handle
     * @param int|string $column
     * @param mixed      $variable
     * @param int        $type
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function bindColumn($handle, $column, &$variable, $type = SQLT_CHR)
    {
        if (null === $type) {
            $type = SQLT_CHR;
        }
        $result = @oci_define_by_name($handle, $column, $variable, $type);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }


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
    public function bindValue($handle, $name, &$variable, $length = -1, $type = SQLT_CHR)
    {
        if (null === $length) {
            $length = -1;
        }
        if (null === $type) {
            $type = SQLT_CHR;
        }
        $result = @oci_bind_by_name($handle, $name, $variable, $length, $type);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }


    /**
     * @param resource $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function commit($handle)
    {
        $result = @oci_commit($handle);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }


    /**
     * @param int    $connectionType
     * @param string $user
     * @param string $password
     * @param string $connectionString
     * @param string $charSet
     * @param int    $sessionMode
     *
     * @return mixed
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function connect($connectionType, $user, $password, $connectionString, $charSet, $sessionMode)
    {

        switch ($connectionType) {
            case self::CONNECTION_TYPE_PERSISTENT:
                $connectFunction = 'oci_pconnect';
                break;
            case self::CONNECTION_TYPE_NEW:
                $connectFunction = 'oci_new_connect';
                break;
            case self::CONNECTION_TYPE_CACHE:
            default:
                $connectFunction = 'oci_connect';
        }
        $user = (string) $user;
        $password = (string) $password;
        $connectionString = (string) $connectionString;
        $charSet = (string) $charSet;
        $sessionMode = (int) $sessionMode;
        $connection = @$connectFunction(
            $user,
            $password,
            $connectionString,
            $charSet,
            $sessionMode
        );
        $this->throwExceptionIfFalse($connection);

        return $connection;
    }


    /**
     * @param resource|null $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function disconnect(&$handle)
    {
        $result = @oci_close($handle);
        $this->throwExceptionIfFalse($result, $handle);
        $handle = null;

        return $this;
    }


    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function execute($handle, $mode = null)
    {
        if (null === $mode) {
            $mode = OCI_COMMIT_ON_SUCCESS;
        }

        $mode = (int) $mode;
        $result = @oci_execute($handle, $mode);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }

    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return array
     */
    public function fetch($handle, $mode)
    {
        if (($mode & OCI_ASSOC) === 0 && ($mode & OCI_NUM) === 0) {
            $mode = OCI_ASSOC + $mode;
        }

        return oci_fetch_array($handle, $mode);
    }


    /**
     * @param resource $handle
     * @param int      $skip
     * @param int      $maxrows
     * @param int      $mode
     *
     * @return array
     */
    public function fetchAll($handle, $skip = 0, $maxrows = -1, $mode = null)
    {
        $mode = $this->addMode($mode, OCI_FETCHSTATEMENT_BY_COLUMN);
        $result = [];
        oci_fetch_all($handle, $result, $skip, $maxrows, $mode);

        return $result;
    }


    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return array
     */
    public function fetchArray($handle, $mode)
    {
        $mode = $this->addMode($mode, OCI_NUM);

        return oci_fetch_array($handle, $mode);
    }

    /**
     * @param resource $handle
     * @param int      $mode
     *
     * @return array
     */
    public function fetchAssoc($handle, $mode)
    {
        $mode = $this->addMode($mode, OCI_ASSOC);

        return oci_fetch_array($handle, $mode);
    }

    /**
     * @param resource $handle
     *
     * @return stdClass
     */
    public function fetchObject($handle)
    {
        return oci_fetch_object($handle);
    }

    /**
     * @param resource|null $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function free(&$handle)
    {
        $result = @oci_free_statement($handle);
        $this->throwExceptionIfFalse($result, $handle);
        $handle = null;

        return $this;
    }

    /**
     * @param resource $handle
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getAffectedRowsNumber($handle)
    {
        $rows = @oci_num_rows($handle);
        $this->throwExceptionIfFalse($rows, $handle);

        return $rows;
    }

    /**
     * @return string
     */
    public function getClientVersion(): string
    {
        return oci_client_version();
    }

    /**
     * @param null|resource $handle
     *
     * @return array
     */
    public function getError($handle = null): array
    {
        $error = is_resource($handle) ? oci_error($handle) : oci_error();
        if (!$error) {
            $error = error_get_last();
        }

        return $error;
    }

    /**
     * @param resource   $handle
     * @param int|string $index
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldName($handle, $index): string
    {
        $result = @oci_field_name($handle, $index);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource $handle
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldNumber($handle): int
    {
        $result = @oci_num_fields($handle);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource   $handle
     * @param int|string $index
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldPrecision($handle, $index)
    {
        $result = @oci_field_precision($handle, $index);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource   $handle
     * @param int|string $index
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldScale($handle, $index)
    {
        $result = @oci_field_scale($handle, $index);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource   $handle
     * @param int|string $index
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldSize($handle, $index)
    {
        $result = @oci_field_size($handle, $index);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource   $handle
     * @param int|string $index
     *
     * @return mixed
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldType($handle, $index)
    {
        $result = @oci_field_type($handle, $index);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource   $handle
     * @param int|string $index
     *
     * @return int
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getFieldTypeRaw($handle, $index)
    {
        $result = @oci_field_type_raw($handle, $index);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param resource $handle
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getServerVersion($handle)
    {
        $version = @oci_server_version($handle);
        $this->throwExceptionIfFalse($version, $handle);

        return $version;
    }

    /**
     * @param resource $handle
     *
     * @return string
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function getStatementType($handle)
    {
        $type = @oci_statement_type($handle);
        $this->throwExceptionIfFalse($type, $handle);

        return $type;
    }

    /**
     * @param int $mode
     *
     * @return bool
     */
    public function isExecuteMode($mode): bool
    {
        return in_array($mode, $this->executeModes, true);
    }

    /**
     * @param resource $handle
     *
     * @return resource
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function newCursor($handle)
    {
        $cursor = @oci_new_cursor($handle);
        $this->throwExceptionIfFalse($cursor, $handle);

        return $cursor;
    }

    /**
     * @param resource $handle
     * @param string   $query
     *
     * @return resource
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function parse($handle, $query)
    {
        $result = oci_parse($handle, $query);
        $this->throwExceptionIfFalse($result, $handle);

        return $result;
    }

    /**
     * @param array|string $variable
     *
     * @return string
     */
    public function quote($variable): string
    {
        if (is_string($variable)) {
            $variable = str_replace("'", "''", $variable);
            $variable = "'" . $variable . "'";

            return $variable;
        }

        if (is_array($variable)) {
            foreach ($variable as $key => $var) {
                $variable[ $key ] = $this->quote($var);
            }

            $variable = implode(',', $variable);
        }

        return $variable;
    }

    /**
     * @param resource $handle
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function rollback($handle)
    {
        $rollbackResult = @oci_rollback($handle);
        if ($rollbackResult === false) {
            throw new Exception("Can't rollback");
        }

        return $this;
    }

    /**
     * @param resource $handle
     * @param string   $identifier
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function setClientIdentifier($handle, $identifier)
    {
        $result = oci_set_client_identifier($handle, $identifier);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }

    /**
     * @param resource $handle
     * @param string   $identifier
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function setClientInfo($handle, $identifier)
    {
        $result = oci_set_client_info($handle, $identifier);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }

    /**
     * @param resource $handle
     * @param string   $moduleName
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function setClientModuleName($handle, string $moduleName)
    {
        $moduleName = mb_substr($moduleName, 0, 32);
        $result = @oci_set_module_name($handle, $moduleName);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }

    /**
     * @param string $edition Oracle Database edition name previously created with the SQL "CREATE EDITION" command.
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function setEdition($edition)
    {
        $result = @oci_set_edition($edition);
        if ($result === false) {
            throw new Exception("Edition setup failed «{$edition}».");
        }

        return $this;
    }

    /**
     * @param resource $handle
     * @param string   $action Oracle Database action for debug information
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function setAction($handle, string $action): self
    {
        $action = mb_substr($action, 0, 32);
        $result = @oci_set_action($handle, $action);
        $this->throwExceptionIfFalse($result, $handle);

        return $this;
    }

    /**
     * @param resource $handle
     * @param int      $size
     *
     * @return $this
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    public function setPrefcth($handle, $size)
    {
        $size = (int) $size;
        $setResult = @oci_set_prefetch($handle, $size);
        $this->throwExceptionIfFalse($setResult, $handle);

        return $this;
    }

    public function setTimeout($handle, int $milliseconds = 0)
    {
        $setResult = @oci_set_call_timeout($handle, $milliseconds);
        $this->throwExceptionIfFalse($setResult, $handle);

        return $this;
    }


    /**
     * if $mode is in $resultMode return $resultMode
     * else return $resultMode + $mode
     *
     * @param int $resultMode sum of modes
     * @param int $mode       mode to check
     *
     * @return int
     */
    protected function addMode($resultMode, $mode)
    {
        if (($resultMode & $mode) === 0) {
            $resultMode = $mode + $resultMode;
        }

        return $resultMode;
    }

    /**
     * @param      $result
     * @param null $handle
     *
     * @throws \nightlinus\OracleDb\Driver\Exception
     */
    protected function throwExceptionIfFalse($result, $handle = null): void
    {
        if (false === $result || $result === null) {
            $error = $this->getError($handle);
            $message = $error[ 'message' ] ?? '';
            $code = $error[ 'code' ] ?? 0;
            switch ($code) {
                case '3136':
                    throw new OperationTimeout($message, $code);
                case '3114':
                    throw new NotConnected($message, $code);
                default:
                    throw new Exception($message, $code);
            }
        }
    }
}
