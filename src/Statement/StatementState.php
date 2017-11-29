<?php
/**
 * Date: 08.12.15
 * Time: 13:18
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */
namespace nightlinus\OracleDb\Statement;

class StatementState
{
    /**
     * List of statement states
     */
    const FREED     = 0x00;
    const PREPARED  = 0x01;
    const DESCRIBED = 0x04;
    const EXECUTED  = 0x08;
    const FETCHING  = 0x10;
    const FETCHED   = 0x02;

    /**
     * @var int
     */
    private $state;

    private function __construct()
    {
    }


    public static function described()
    {
        return self::in(self::DESCRIBED);
    }

    public static function executed()
    {
        return self::in(self::EXECUTED);
    }

    public static function fetched()
    {
        return self::in(self::FETCHED);
    }

    public static function fetching()
    {
        return self::in(self::FETCHING);
    }

    public static function freed()
    {
        return self::in(self::FREED);
    }

    public static function in($state = self::FREED)
    {
        $inst = new self();
        $inst->state = $state;

        return new self($state);
    }

    public static function prepared()
    {
        return self::in(self::PREPARED);
    }

    public function isFetchable()
    {
        return $this->state === self::EXECUTED;
    }

    public function isNotFetchedYet()
    {
        return $this->state !== self::FETCHED;
    }

    public function isPrepared()
    {
        return $this->state >= self::PREPARED;
    }

    public function isSafeToFree()
    {
        return $this->state < self::FETCHING;
    }
}
