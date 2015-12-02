<?php
/**
 * Date: 03.12.15
 * Time: 0:21
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb;

class FieldDescription
{
    private $name;

    private $size;

    private $precison;

    private $scale;

    private $type;

    private $rawType;

    public function __construct($name, $size, $precison, $scale, $type, $rawType)
    {
        $this->name = $name;
        $this->size = $size;
        $this->precison = $precison;
        $this->scale = $scale;
        $this->type = $type;
        $this->rawType = $rawType;
    }

    public function name()
    {
        return $this->name;
    }

    public function precison()
    {
        return $this->precison;
    }

    public function rawType()
    {
        return $this->rawType;
    }

    public function scale()
    {
        return $this->scale;
    }

    public function size()
    {
        return $this->size;
    }

    public function type()
    {
        return $this->type;
    }
}
