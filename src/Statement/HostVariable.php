<?php
/**
 * Date: 03.12.15
 * Time: 0:38
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb\Statement;

/**
 * Class HostVariable
 *
 * @package nightlinus\OracleDb
 */
class HostVariable
{
    /**
     * @type mixed
     */
    private $value;

    /**
     * @type int
     */
    private $length;

    /**
     * @type int
     */
    private $type;

    /**
     * HostVariable constructor.
     *
     * @param $value
     * @param $length
     * @param $type
     */
    private function __construct($value, $length = null, $type = null)
    {
        $this->value = $value;
        $this->length = $length;
        $this->type = $type;
    }

    /**
     * @param mixed    $value
     * @param null|int $length
     * @param null|int $type
     *
     * @return HostVariable
     */
    public static function with($value, $length = null, $type = null)
    {
        if ($value instanceof HostVariable) {
            return $value;
        }

        return new self($value, $length, $type);
    }

    /**
     * @return int
     */
    public function length()
    {
        return $this->length;
    }

    /**
     * @return int
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }
}
