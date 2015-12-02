<?php
/**
 * Date: 02.12.15
 * Time: 16:13
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb\Utills;

/**
 * Class Alias
 *
 * @package nightlinus\OracleDb\Utills
 */
class Alias
{
    const PREFIX = 'z__';

    private $name;

    public static function unique()
    {
        $hash = uniqid(self::PREFIX, true);
        $hash = str_replace('.', '', $hash);
        $self = new self();
        $self->name = $hash;

        return $self;
    }

    public function __toString()
    {
        return $this->name;
    }
}
