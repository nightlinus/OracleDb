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


use nightlinus\OracleDb\Database;

/**
 * Class Model
 * @package nightlinus\OracleDb\Model
 */
class Model
{

    /**
     * @var Database
     */
    protected $db;

    /**
     * @param $db
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * @param Relation $relation
     */
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

    /**
     * @param Relation $relation
     *
     * @return $this
     */
    public function getColumns(Relation $relation)
    {
        $owner = $relation->getOwner();
        $name = $relation->getName();
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
        $statement = $this->db->query($sql, [ 'b_name' => $name, 'b_ownew' => $owner ]);
        foreach ($statement as $row) {
            $column = new Column(
                $row['COLUMN_ID'],
                $row['DATA_LENGTH'],
                $row['COLUMN_NAME'],
                $row['NULLABLE'],
                $row['DATA_PRECISION'],
                $row['DATA_SCALE'],
                $row['DATA_TYPE']
            );
            $relation->addColumn($column);
        }

        return $this;
    }
}
