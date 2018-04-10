<?php
declare(strict_types=1);
/**
 * Date: 10.04.18
 * Time: 0:01
 *
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 */

namespace nightlinus\OracleDb\tests;

use nightlinus\OracleDb\Config;
use nightlinus\OracleDb\Database;
use nightlinus\OracleDb\DatabaseFactory;
use PHPUnit\Framework\TestCase;
use function getenv;
use const PHP_EOL;

class DatabaseTest extends TestCase
{
    public function sut(): Database
    {
        $server = getenv('DB_HOST');
        $user = getenv('DB_USER');
        $password = getenv('DB_PASSWORD');
        $config = [ Config::CONNECTION_CACHE => 1 ];

        return DatabaseFactory::fromCredentials($user, $password, $server, $config);
    }

    /**
     * @test
     */
    public function it_call_destruct()
    {
        $this->sut()->connect();

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function it_cache_connection()
    {
        $sut = $this->sut();
        $expected = $this->handlerAsString($sut);
        $sut2 = $this->sut();
        $actual = $this->handlerAsString($sut2);

        $sut = null;
        $sut2 = null;
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_release_connection()
    {
        $sut = $this->sut();
        $expected = $this->handlerAsString($sut);

        $sut = null;

        $sut2 = $this->sut();
        $actual = $this->handlerAsString($sut2);
        $sut2 = null;

        $this->assertNotEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_release_connection_after_sql_was_executed()
    {
        $sut = $this->sut();
        $expected = $this->handlerAsString($sut);

        $sut->fetchValue("SELECT 123 FROM DUAL");
        $sut = null;

        $sut2 = $this->sut();
        $actual = $this->handlerAsString($sut2);
        $sut2 = null;

        $this->assertNotEquals($expected, $actual);
    }

    private function handlerAsString(Database $sut): string
    {
        $sut->connect();

        return (string) $sut->getConnection();
    }
}

