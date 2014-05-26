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

    protected $columns;

    protected $constraints;

    protected $indexes;

    protected $name;

    protected $owner;

    /**
     * @param string $name
     * @param string $owner
     * @param array  $columns
     * @param array  $constraints
     * @param array  $indexes
     */
    public function __construct($name, $owner, $columns = [ ], $constraints = [ ], $indexes = [ ])
    {
        $this->constraints = $constraints;
        $this->columns = $columns;
        $this->indexes = $indexes;
        $this->name = $name;
        $this->owner = $owner;
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


}
