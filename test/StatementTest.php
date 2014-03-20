<?php
/**
 * Date: 14.11.13
 * Time: 16:18
 *
 * @category 
 * @package  OracleDb
 * @author   nightlinus <user@localhost>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  
 * @link     
 */

namespace OracleDb\test;

use OracleDb\Statement;

class StatementTest extends \PHPUnit_Framework_TestCase
{
    protected $instance;
    protected $db;

    protected function setUp()
    {
        parent::setUp();
        $arr = ['user', 'password', 'connection string'];
        $this->db = $this->getMockBuilder('\OracleDb\Db')
            ->setConstructorArgs($arr)
            ->getMock();
        $this->instance = null;
    }

    public function testTupleGenerator()
    {
        $this->instance = new Statement($this->db, 'test');
        $arr = [
           0 => [
               'TEST_NUMBER' => 1,
               'TEST_ACTION' => 2,
               'TEST_STRING' => 'three'
           ],
           1 => [
               'TEST_NUMBER' => 11,
               'TEST_ACTION' => 12,
               'TEST_STRING' => 'one three'
           ],
           2 => [
               'TEST_NUMBER' => 21,
               'TEST_ACTION' => 22,
               'TEST_STRING' => 'two three'
           ],
           3 => [
               'TEST_NUMBER' => 31,
               'TEST_ACTION' => 32,
               'TEST_STRING' => 'three three'
           ]
        ];
        $func = function () use ($arr) {
            foreach ($arr as $a) {
                yield $a;
            }
        };

        $a = new \ReflectionMethod($this->instance, 'tupleGenerator');
        $a->setAccessible(true);
        $i = 0;
        $gen = $a->invoke($this->instance, $func);
        foreach ($gen->current() as $ret) {
            $this->assertEquals(
                $arr[$i++],
                $ret,
                'Returned value from generator must be equal to stubbed one'
            );
        }
    }
}
