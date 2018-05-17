<?php
declare(strict_types=1);
/**
 * Date: 17.05.18
 * Time: 23:17
 *
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 */

namespace nightlinus\OracleDb\tests\Statement;

use nightlinus\OracleDb\Statement\HostVariable;
use PHPUnit\Framework\TestCase;

class HostVariableTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_initialized_from_hash()
    {
        $sut = HostVariable::with([ 'value' => '1', 'length' => '100', 'type' => SQLT_CHR ]);
        $this->assertEquals('1', $sut->value());
        $this->assertEquals(100, $sut->length());
        $this->assertEquals(SQLT_CHR, $sut->type());
    }

    /**
     * @test
     */
    public function it_can_be_initialized_from_array()
    {
        $sut = HostVariable::with([ '0' => '1', '1' => '100', '2' => SQLT_CHR ]);
        $this->assertEquals('1', $sut->value());
        $this->assertEquals(100, $sut->length());
        $this->assertEquals(SQLT_CHR, $sut->type());
    }

    /**
     * @test
     */
    public function it_can_be_initialized_from_values()
    {
        $sut = HostVariable::with('1', '100', SQLT_CHR);
        $this->assertEquals('1', $sut->value());
        $this->assertEquals(100, $sut->length());
        $this->assertEquals(SQLT_CHR, $sut->type());
    }
}

