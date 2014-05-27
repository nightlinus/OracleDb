<?php
/**
 * Date: 27.05.14
 * Time: 0:07
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
 * Class ConstraintColumn
 * @package nightlinus\OracleDb\Model
 */
class ConstraintColumn
{

    protected $constraintName;

    protected $name;

    protected $owner;

    protected $table;

    /**
     * @param $name
     * @param $owner
     * @param $table
     * @param $constraintName
     */
    public function __construct($name, $owner, $table, $constraintName)
    {
        $this->constraintName = $constraintName;
        $this->name = $name;
        $this->owner = $owner;
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getConstraintName()
    {
        return $this->constraintName;
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
    public function getTable()
    {
        return $this->table;
    }
}
