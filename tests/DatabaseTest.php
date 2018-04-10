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
use function iterator_to_array;

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

    /**
     * @test
     */
    public function it_binds_variables()
    {
        $sut = $this->sut();
        $sql = "SELECT :0, :1 FROM DUAL";

        $actual = $sut->fetchArray($sql, [ '23', 'xyz' ]);

        $this->assertEquals([ [ '23', 'xyz' ] ], $actual);
    }

    /**
     * @test
     */
    public function it_fetch_assoc()
    {
        $sut = $this->sut();
        $actual = $sut->fetchAssoc($this->testSql());
        $this->assertInternalType('array', $actual);
        $this->assertEquals(
            [
                [
                    'TEXT' => 'a',
                    'IDX' => '1',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'b',
                    'IDX' => '2',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'c',
                    'IDX' => '3',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'd',
                    'IDX' => '4',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'e',
                    'IDX' => '5',
                    'CONST' => 'const',
                ],
            ],
            $actual
        );
    }

    /**
     * @test
     */
    public function it_fetch_array()
    {
        $sut = $this->sut();
        $actual = $sut->fetchArray($this->testSql());
        $this->assertInternalType('array', $actual);
        $this->assertEquals(
            [
                [
                    'a',
                    '1',
                    'const',
                ],
                [
                    'b',
                    '2',
                    'const',
                ],
                [
                    'c',
                    '3',
                    'const',
                ],
                [
                    'd',
                    '4',
                    'const',
                ],
                [
                    'e',
                    '5',
                    'const',
                ],
            ],
            $actual
        );
    }

    /**
     * @test
     */
    public function it_fetch_one()
    {
        $sut = $this->sut();
        $actual = $sut->fetchOne($this->testSql());
        $this->assertInternalType('array', $actual);
        $this->assertEquals(
            [
                'TEXT' => 'a',
                'IDX' => '1',
                'CONST' => 'const',
            ],
            $actual
        );
    }

    /**
     * @test
     */
    public function it_fetch_value()
    {
        $sut = $this->sut();
        $actual = $sut->fetchValue($this->testSql());
        $this->assertInternalType('string', $actual);
        $this->assertEquals('a', $actual);
    }

    /**
     * @test
     */
    public function it_yields_assoc()
    {
        $sut = $this->sut();
        $actual = $sut->yieldAssoc($this->testSql());

        $this->assertInstanceOf(\Generator::class, $actual);
        $this->assertEquals(
            [
                [
                    'TEXT' => 'a',
                    'IDX' => '1',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'b',
                    'IDX' => '2',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'c',
                    'IDX' => '3',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'd',
                    'IDX' => '4',
                    'CONST' => 'const',
                ],
                [
                    'TEXT' => 'e',
                    'IDX' => '5',
                    'CONST' => 'const',
                ],
            ],
            iterator_to_array($actual)
        );
    }

    /**
     * @test
     */
    public function it_yields_array()
    {
        $sut = $this->sut();
        $actual = $sut->yieldArray($this->testSql());

        $this->assertInstanceOf(\Generator::class, $actual);
        $this->assertEquals(
            [
                [
                    'a',
                    '1',
                    'const',
                ],
                [
                    'b',
                    '2',
                    'const',
                ],
                [
                    'c',
                    '3',
                    'const',
                ],
                [
                    'd',
                    '4',
                    'const',
                ],
                [
                    'e',
                    '5',
                    'const',
                ],
            ],
            iterator_to_array($actual)
        );
    }

    /**
     * @test
     */
    public function it_yields_column()
    {
        $sut = $this->sut();
        $actual = $sut->yieldColumn($this->testSql());

        $this->assertInstanceOf(\Generator::class, $actual);
        $this->assertEquals(
            [
                'a',
                'b',
                'c',
                'd',
                'e',
            ],
            iterator_to_array($actual)
        );
    }

    /**
     * @test
     */
    public function it_yields_callback()
    {
        $sut = $this->sut();
        $callback = function ($row, $key) {
            return [ $key => $row[ 'TEXT' ] . $row[ 'IDX' ] ];
        };
        $actual = $sut->yieldCallback($this->testSql(), [], $callback);

        $this->assertInstanceOf(\Generator::class, $actual);
        $this->assertEquals(
            [
                'a1',
                'b2',
                'c3',
                'd4',
                'e5',
            ],
            iterator_to_array($actual)
        );
    }

    private function handlerAsString(Database $sut): string
    {
        $sut->connect();

        return (string) $sut->getConnection();
    }

    private function testSql(): string
    {
        return "WITH TEST_DATA (TEXT, IDX, CONST) AS (SELECT
                           'a',
                           1,
                           'const'
                         FROM DUAL
                         UNION ALL
                         SELECT
                           chr(ascii(TEXT) + 1),
                           IDX + 1,
                           'const'
                         FROM TEST_DATA
                         WHERE IDX < 5)

                SELECT *
                FROM TEST_DATA";
    }
}

