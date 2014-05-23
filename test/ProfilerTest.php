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

namespace nightlinus\OracleDb\test;


use nightlinus\OracleDb\Exception;
use nightlinus\OracleDb\Profiler;

/**
 * Class ProfilerTest
 * @package nightlinus\OracleDb\test
 */
class ProfilerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var Profiler
     */
    protected $instance;

    public function testEnd()
    {
        $this->instance->start('timer');
        $sleep = 2;
        sleep($sleep);
        $this->instance->end();
        $result = $this->instance->getLastProfile();
        $time = floor($result[ 'elapsed' ]);
        $this->assertEquals($sleep, $time);

    }

    /**
     * @expectedException Exception
     */
    public function testEndException()
    {
        $this->instance->end();
    }

    /**
     * @depends testGetProfiles
     */
    public function testGetLastProfile()
    {
        $result = $this->instance->getProfiles();
        $this->assertEquals(end($result), $this->instance->getLastProfile());
    }

    public function testGetProfiles()
    {
        $this->assertAttributeEquals($this->instance->getProfiles(), 'profiles', $this->instance);
    }

    public function testStart()
    {

        $result = $this->instance->getProfiles()[ 0 ];
        $this->assertEquals('test', $result[ 'sql' ], 'SqlText shoud be saved in profile.');


        $result = $this->instance->getProfiles()[ 1 ];
        $this->assertEquals('test2', $result[ 'sql' ], 'SqlText shoud be saved in profile.');
    }

    protected function setUp()
    {
        parent::setUp();
        $this->instance = new Profiler();

        $this->instance->start('test');
        $this->assertAttributeCount(1, 'profiles', $this->instance, 'Profiles number shoud increase with start() call');
        $this->instance->end();

        $this->instance->start('test2');
        $this->assertAttributeCount(2, 'profiles', $this->instance, 'Profiles number shoud increase with start() call');
        $this->instance->end();

    }
}
