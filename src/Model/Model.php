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
use nightlinus\OracleDb\Exception;

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
        $sql = "SELECT t_atc.COLUMN_NAME,
                       t_atc.DATA_TYPE,
                       t_atc.DATA_LENGTH,
                       t_atc.DATA_PRECISION,
                       t_atc.DATA_SCALE,
                       t_atc.NULLABLE,
                       t_atc.COLUMN_ID,
                       t_acc.COMMENTS
                FROM ALL_TAB_COLUMNS t_atc
                     JOIN ALL_COL_COMMENTS t_acc
                     ON t_acc.COLUMN_NAME = t_atc.COLUMN_NAME
                      AND t_acc.OWNER = t_atc.OWNER
                      AND t_acc.TABLE_NAME = t_atc.TABLE_NAME
                WHERE t_atc.OWNER = :b_owner
                AND t_atc.TABLE_NAME = :b_name";
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
                $row[ 'DATA_TYPE' ],
                $row[ 'COMMENTS']
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
                       STATUS,
                       TABLE_NAME
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
                $row[ 'CONSTRAINT_TYPE' ],
                $row[ 'TABLE_NAME' ]
            );
            $relation->addConstraint($constraint);
            $this->getConstraintColumns($constraint);
            $constraints[ $constraint->getName() ] = $constraint;
        }

        return $constraints;
    }

    /**
     * @param string $name
     * @param string $owner
     *
     * @return Constraint
     */
    public function getConstraint($name, $owner)
    {
        $sql = "SELECT CONSTRAINT_NAME,
                       CONSTRAINT_TYPE,
                       R_OWNER,
                       R_CONSTRAINT_NAME,
                       STATUS,
                       TABLE_NAME
                FROM ALL_CONSTRAINTS
                WHERE OWNER = :b_owner
                  AND CONSTRAINT_NAME = :b_name";
        $statement = $this->db->query($sql, [ 'b_name' => $name, 'b_owner' => $owner ]);
        $row = $statement->fetchOne();
        $constraint = new Constraint(
            $row[ 'CONSTRAINT_NAME' ],
            $row[ 'R_CONSTRAINT_NAME' ],
            $row[ 'R_OWNER' ],
            $row[ 'STATUS' ],
            $row[ 'CONSTRAINT_TYPE' ],
            $row[ 'TABLE_NAME' ]
        );
        $this->getConstraintColumns($constraint);

        return $constraint;
    }

    /**
     * @param string $name
     * @param string $owner
     *
     * @throws \nightlinus\OracleDb\Exception
     * @return Relation
     */
    public function getRelation($name, $owner)
    {
        $sql = "SELECT t_at.OWNER,
                       t_at.TABLE_NAME,
                       t_at.TABLESPACE_NAME,
                       t_at.STATUS,
                       t_at.NUM_ROWS,
                       t_atc.COMMENTS
                FROM ALL_TABLES t_at
                     JOIN ALL_TAB_COMMENTS t_atc
                     ON t_atc.OWNER = t_at.OWNER
                      AND t_atc.TABLE_NAME = t_at.TABLE_NAME
                WHERE t_at.OWNER = :b_owner
                AND t_at.TABLE_NAME = :b_name";
        $statement = $this->db->query($sql, [ 'b_name' => $name, 'b_owner' => $owner ]);
        $row = $statement->fetchOne();
        if (!$row) {
            throw new Exception("No table $owner.$name or you dont have enough permissions");
        }
        $relation = new Relation(
            $row['TABLE_NAME'],
            $row['OWNER'],
            $row['TABLESPACE_NAME'],
            $row['STATUS'],
            $row['NUM_ROWS'],
            $row['COMMENTS']
        );
        $this->getColumns($relation);
        $this->getConstraints($relation);

        return $relation;
    }
}
