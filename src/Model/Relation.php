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

    protected $constraints;

    protected $fields;

    protected $indexes;

    protected $name;

    protected $owner;

    /**
     * @param $name
     * @param $owner
     * @param $fields
     * @param $constraints
     * @param $indexes
     */
    public function __construct($name, $owner, $fields = [], $constraints = [], $indexes = [])
    {
        $this->constraints = $constraints;
        $this->fields = $fields;
        $this->indexes = $indexes;
        $this->name = $name;
        $this->owner = $owner;
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
    public function getFields()
    {
        return $this->fields;
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


}
