<?php
/**
 * Date: 23.05.14
 * Time: 16:52
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
 * Class Column
 * @package nightlinus\OracleDb\Model
 */
class Column
{

    protected $comment;

    protected $id;

    protected $length;

    protected $name;

    protected $nullable;

    protected $precision;

    protected $scale;

    protected $type;

    /**
     * @param $id
     * @param $length
     * @param $name
     * @param $nullable
     * @param $precision
     * @param $scale
     * @param $type
     */
    public function __construct($id, $length, $name, $nullable, $precision, $scale, $type, $comment)
    {
        $this->id = $id;
        $this->length = $length;
        $this->name = $name;
        $this->nullable = $nullable;
        $this->precision = $precision;
        $this->scale = $scale;
        $this->type = $type;
        $this->comment = $comment;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
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
    public function getNullable()
    {
        return $this->nullable;
    }

    /**
     * @return mixed
     */
    public function getPrecision()
    {
        return $this->precision;
    }

    /**
     * @return mixed
     */
    public function getScale()
    {
        return $this->scale;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }
}
