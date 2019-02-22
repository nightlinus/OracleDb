<?php
declare(strict_types=1);
/**
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
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

    /** @var int */
    private $state;

    private function __construct()
    {
    }

    private static function in(int $state): self
    {
        $inst = new self();
        $inst->state = $state;

        return $inst;
    }

    public static function initialize(): self
    {
        return self::in(self::FREED);
    }

    public function described(): self
    {
        return self::in(self::DESCRIBED);
    }

    public function executed(): self
    {
        return self::in(self::EXECUTED);
    }

    public function fetched(): self
    {
        return self::in(self::FETCHED);
    }

    public function fetching(): self
    {
        return self::in(self::FETCHING);
    }

    public function freed(): self
    {
        return self::in(self::FREED);
    }

    public function prepared(): self
    {
        return self::in(self::PREPARED);
    }

    public function isFetchable(): bool
    {
        return $this->state === self::EXECUTED;
    }

    public function isNotFetchedYet(): bool
    {
        return $this->state !== self::FETCHED;
    }

    public function isPrepared(): bool
    {
        return $this->state >= self::PREPARED;
    }

    public function isSafeToFree(): bool
    {
        return $this->state < self::FETCHING;
    }
}
