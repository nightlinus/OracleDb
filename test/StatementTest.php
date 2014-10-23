<?php
/**
 * Date: 14.10.14
 * Time: 10:20
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb\Tests;

use nightlinus\OracleDb\Database;
use nightlinus\OracleDb\Driver\AbstractDriver;
use nightlinus\OracleDb\Driver\Oracle;
use nightlinus\OracleDb\Statement;

class StatementTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @type \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dbMock;

    /**
     * @type \PHPUnit_Framework_MockObject_MockObject
     */
    protected $driverMock;

    public function setUp()
    {
        $this->getDbMock();
    }

    public function testBind()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'prepare' ])
                          ->getMock();

        $bindings = [
            "lol"  => 1,
            "digit",
            [ "values", "length", "type" ],
            "khan" => [ "value" => '101', "length" => 2000, "type" => "testType" ],
        ];

        $this->driverMock->expects($this->exactly(4))
                         ->method("bindValue")
                         ->withConsecutive(
                             [ null, "lol", 1 ],
                             [ null, 0, "digit" ],
                             [ null, 1, "values", "length", "type" ],
                             [ null, "khan", 101, 2000, "testType" ]
                         );


        /**
         * @type Statement $statement
         */
        $statement->bind($bindings);

        $this->assertEquals(count($bindings), count($statement->bindings));
    }

    public function testBindCallPrepare()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'prepare' ])
                          ->getMock();
        $statement->expects($this->once())->method("prepare");

        $bindings = [ "1" ];
        /**
         * @type Statement $statement
         */
        $statement->bind($bindings);
    }

    public function testBindStatement()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'prepare' ])
                          ->getMock();

        $bindStatement = $this->getMockBuilder(Statement::class)
                              ->setConstructorArgs([ $this->dbMock ])
                              ->setMethods([ 'prepare' ])
                              ->getMock();
        $bindStatement->expects($this->once())
                      ->method("prepare");

        /**
         * @type AbstractDriver $driver
         */
        $driver = $this->driverMock;
        $this->driverMock->expects($this->exactly(1))
                         ->method("bindValue")
                         ->with(
                             null,
                             "statement",
                             null,
                             null,
                             $driver::TYPE_CURSOR
                         );


        $bindings = [ "statement" => $bindStatement ];

        /**
         * @type Statement $statement
         */
        $statement->bind($bindings);

    }

    public function testBindWithEmptyArray()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'prepare' ])
                          ->getMock();

        $this->driverMock->expects($this->never())->method("bindValue");
        $statement->expects($this->never())->method("prepare");
        $bindings = [ ];

        /**
         * @type Statement $statement
         */
        $statement->bind($bindings);

    }

    public function testBindWithNotArray()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'prepare' ])
                          ->getMock();

        $this->driverMock->expects($this->never())->method("bindValue");
        $statement->expects($this->never())->method("prepare");
        $bindings = 10;

        /**
         * @type Statement $statement
         */
        $statement->bind($bindings);

    }

    public function testFetchArray()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'getResultObject' ])
                          ->getMock();

        $mode = 100;
        $statement->expects($this->once())
                  ->method("getResultObject")
                  ->with(null, Statement::FETCH_ARRAY, $mode);

        /**
         * @type Statement $statement
         */
        $statement->fetchArray($mode);
    }

    public function testFetchAssoc()
    {
        $statement = $this->getMockBuilder(Statement::class)
                          ->setConstructorArgs([ $this->dbMock, 'sql' ])
                          ->setMethods([ 'getResultObject' ])
                          ->getMock();

        $mode = 500;
        $statement->expects($this->once())
                  ->method("getResultObject")
                  ->with(null, Statement::FETCH_ASSOC, $mode);

        /**
         * @type Statement $statement
         */
        $statement->fetchAssoc($mode);
    }

    protected function getDbMock()
    {
        $this->dbMock = $this->getMock(Database::class, [ ], [ "TEST", "TEST" ]);
        $this->driverMock = $this->getMock(Oracle::class);
        $this->dbMock->expects($this->any())->method("getDriver")->will($this->returnValue($this->driverMock));
    }
}

