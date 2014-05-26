<?php
/**
 * Date: 26.05.14
 * Time: 9:05
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <user@localhost>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb\Model;


use nightlinus\OracleDb\Db;

/**
 * Class Model
 * @package nightlinus\OracleDb\Model
 */
class Model
{

    /**
     * @var Db
     */
    protected $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getConstraints(Relation $relation)
    {
        $sql = "SELECT CONSTRAINT_NAME,
                       CONSTRAINT_TYPE,
                       R_OWNER,
                       R_CONSTRAINT_NAME,
                       STATUS
                FROM ALL_CONSTRAINTS
                WHERE OWNER = 'HERMES'
                  AND TABLE_NAME = 'ORDER_TEMPLATE'";
    }

    public function getFields(Relation $relation)
    {
        $sql = "SELECT COLUMN_NAME,
                       DATA_TYPE,
                       DATA_LENGTH,
                       DATA_PRECISION,
                       DATA_SCALE,
                       NULLABLE,
                       COLUMN_ID
                FROM ALL_TAB_COLUMNS
                WHERE OWNER = 'HERMES'
                  AND TABLE_NAME = 'ORDER_VALUE_SOURCE'";

    }
}
