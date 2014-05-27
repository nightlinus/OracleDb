<?php
/**
 * Date: 23.05.14
 * Time: 11:49
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <user@localhost>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb\Model;

/**
 * Class Relation
 * @package nightlinus\OracleDb\Model
 */
class Relation
{

    /**
     * @var Column[]
     */
    protected $columns;

    protected $comment;

    /**
     * @var Constraint[]
     */
    protected $constraints;

    protected $indexes;

    protected $name;

    protected $owner;

    protected $rowCount;

    protected $status;

    protected $tableSpace;

    /**
     * @param       $name
     * @param       $owner
     * @param       $tableSpace
     * @param       $status
     * @param       $rowCount
     * @param       $comment
     * @param array $columns
     * @param array $constraints
     * @param array $indexes
     */
    public function __construct(
        $name,
        $owner,
        $tableSpace,
        $status,
        $rowCount,
        $comment,
        $columns = [ ],
        $constraints = [ ],
        $indexes = [ ]
    ) {
        $this->columns = $columns;
        $this->comment = $comment;
        $this->constraints = $constraints;
        $this->indexes = $indexes;
        $this->name = $name;
        $this->owner = $owner;
        $this->rowCount = $rowCount;
        $this->status = $status;
        $this->tableSpace = $tableSpace;
    }


    /**
     * @param Column $column
     *
     * @return $this
     */
    public function addColumn(Column $column)
    {
        $name = $column->getName();
        $this->columns[ $name ] = $column;

        return $this;
    }

    /**
     * @param Constraint $constraint
     *
     * @return $this
     */
    public function addConstraint(Constraint $constraint)
    {
        $name = $constraint->getName();
        $this->constraints[ $name ] = $constraint;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return mixed
     */
    public function getConstraints()
    {
        return $this->constraints;
    }

    /**
     * @return mixed
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return mixed
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return mixed
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @return mixed
     */
    public function getRowCount()
    {
        return $this->rowCount;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getTableSpace()
    {
        return $this->tableSpace;
    }
}
