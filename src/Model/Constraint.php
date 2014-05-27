<?php
/**
 * Date: 23.05.14
 * Time: 16:53
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
 * Class Constraint
 * @package nightlinus\OracleDb\Model
 */
class Constraint
{
    const TYPE_CHECK = 'C';

    const TYPE_FOREIGN_KEY = 'R';

    const TYPE_PRIMARY_KEY = 'P';

    const TYPE_UNIQUE = 'U';

    const TYPE_WITH_CHECK = 'V';

    const TYPE_WITH_READ_ONLY = 'O';

    protected $name;

    protected $referenceConstraint;

    protected $referenceOwner;

    protected $status;

    protected $type;

    protected $columns = [];

    /**
     * @param $name
     * @param $referenceConstraint
     * @param $referenceOwner
     * @param $status
     * @param $type
     */
    public function __construct($name, $referenceConstraint, $referenceOwner, $status, $type)
    {
        $this->name = $name;
        $this->referenceConstraint = $referenceConstraint;
        $this->referenceOwner = $referenceOwner;
        $this->status = $status;
        $this->type = $type;
    }

    /**
     * @param ConstraintColumn $constraintColumn
     *
     * @return $this
     */
    public function addColumn(ConstraintColumn $constraintColumn)
    {
        $name = $constraintColumn->getName();
        $this->columns[ $name ] = $constraintColumn;

        return $this;
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
    public function getReferenceConstraint()
    {
        return $this->referenceConstraint;
    }

    /**
     * @return mixed
     */
    public function getReferenceOwner()
    {
        return $this->referenceOwner;
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
    public function getType()
    {
        return $this->type;
    }
}
