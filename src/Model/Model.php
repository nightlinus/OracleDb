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
     *
     * @return Column[]
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
                WHERE OWNER = :b_owner
                  AND TABLE_NAME = :b_name";
        $statement = $this->db->query($sql, [ 'b_name' => $name, 'b_owner' => $owner ]);
        $columns = [ ];
        foreach ($statement as $row) {
            $column = new Column(
                $row[ 'COLUMN_ID' ],
                $row[ 'DATA_LENGTH' ],
                $row[ 'COLUMN_NAME' ],
                $row[ 'NULLABLE' ],
                $row[ 'DATA_PRECISION' ],
                $row[ 'DATA_SCALE' ],
                $row[ 'DATA_TYPE' ]
            );
            $relation->addColumn($column);
            $columns[ $column->getName() ] = $column;
        }

        return $columns;
    }

    /**
     * @param Constraint $constraint
     *
     * @return ConstraintColumn[]
     */
    public function getConstraintColumns(Constraint $constraint)
    {
        $name = $constraint->getName();
        $sql = "SELECT OWNER,
                       CONSTRAINT_NAME,
                       TABLE_NAME,
                       COLUMN_NAME
                FROM ALL_CONS_COLUMNS
                WHERE CONSTRAINT_NAME = :b_name";
        $statement = $this->db->query($sql, [ 'b_name' => $name ]);
        $columns = [ ];
        foreach ($statement as $row) {
            $constraintColumn = new ConstraintColumn(
                $row[ 'COLUMN_NAME' ],
                $row[ 'OWNER' ],
                $row[ 'TABLE_NAME' ],
                $row[ 'CONSTRAINT_NAME' ]
            );
            $constraint->addColumn($constraintColumn);
            $columns[ $constraintColumn->getName() ] = $constraintColumn;
        }

        return $columns;
    }

    /**
     * @param Relation $relation
     *
     * @return Constraint[]
     */
    public function getConstraints(Relation $relation)
    {
        $owner = $relation->getOwner();
        $name = $relation->getName();
        $sql = "SELECT CONSTRAINT_NAME,
                       CONSTRAINT_TYPE,
                       R_OWNER,
                       R_CONSTRAINT_NAME,
                       STATUS
                FROM ALL_CONSTRAINTS
                WHERE OWNER = :b_owner
                  AND TABLE_NAME = :b_name";
        $statement = $this->db->query($sql, [ 'b_name' => $name, 'b_owner' => $owner ]);
        $constraints = [ ];
        foreach ($statement as $row) {
            $constraint = new Constraint(
                $row[ 'CONSTRAINT_NAME' ],
                $row[ 'R_CONSTRAINT_NAME' ],
                $row[ 'R_OWNER' ],
                $row[ 'STATUS' ],
                $row[ 'CONSTRAINT_TYPE' ]
            );
            $relation->addConstraint($constraint);
            $this->getConstraintColumns($constraint);
            $constraints[ $constraint->getName() ] = $constraint;
        }

        return $constraints;
    }

    /**
     * @param $name
     * @param $owner
     *
     * @return Relation
     */
    public function getRelation($name, $owner)
    {
        $relation = new Relation($name, $owner);
        $this->getColumns($relation);
        $this->getConstraints($relation);

        return $relation;
    }
}
