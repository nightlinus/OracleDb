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
    protected $name;

    protected $referenceConstraint;

    protected $referenceOwner;

    protected $status;

    protected $type;

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
