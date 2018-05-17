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

use function is_array;

final class HostVariable
{
    /**
     * @var mixed
     */
    private $value;

    /**
     * @var int
     */
    private $length;

    /**
     * @var int
     */
    private $type;

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
    public static function with($value, $length = null, $type = null): self
    {
        if ($value instanceof self) {
            return $value;
        }

        $bindValue = $value;
        if (is_array($value)) {
            $bindValue = $value[ 0 ] ?? $value[ 'value' ] ?? null;
            $length = $value[ 1 ] ?? $value[ 'length' ] ?? null;
            $type = $value[ 2 ] ?? $value[ 'type' ] ?? null;
        }

        return new self($bindValue, $length, $type);
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
