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
    private const FREED = 0x00;
    private const PREPARED = 0x01;
    private const DESCRIBED = 0x04;
    private const EXECUTED = 0x08;
    private const FETCHING = 0x10;
    private const FETCHED = 0x02;

    /**
     * @var int
     */
    private $state;

    private function __construct()
    {
    }

    public static function in(string $state): self
    {
        $inst = new self();
        $inst->state = $state;

        return $inst;
    }

    public static function initialize(): self
    {
        return self::in(self::FREED);
    }


    public function described()
    {
        return self::in(self::DESCRIBED);
    }

    public function executed()
    {
        return self::in(self::EXECUTED);
    }

    public function fetched()
    {
        return self::in(self::FETCHED);
    }

    public function fetching()
    {
        return self::in(self::FETCHING);
    }

    public function freed()
    {
        return self::in(self::FREED);
    }

    public function prepared()
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
