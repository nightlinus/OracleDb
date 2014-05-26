<?php
/**
 * Date: 14.11.13
 * Time: 16:19
 *
 * @category
 * @package  OracleDb
 * @author   nightlinus <user@localhost>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version
 * @link
 */

namespace nightlinus\OracleDb\test;

use nightlinus\OracleDb\Db;

/**
 * Class E2eTest
 * @package OracleDb\test
 */
class E2eTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Db
     */
    protected $db;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $q = "DROP TABLE TEST_ORACLEDB;
                CREATE TABLE TEST_ORACLEDB (
                  TEST_NUMBER NUMBER,
                  TEST_DATE DATE,
                  TEST_NVARCHAR NVARCHAR2(2000),
                  TEST_VARCHAR VARCHAR2(20 BYTE),
                  TEST_BLOB BLOB,
                  TEST_CLOB CLOB
                );
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('1',to_date('20.11.13 08:20:23','DD.MM.RR HH24:MI:SS'),'Ололошеньки','Ololo', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('2',to_date('19.11.13 08:21:26','DD.MM.RR HH24:MI:SS'),'Тынц','Tync', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('3',to_date('18.11.13 08:22:21','DD.MM.RR HH24:MI:SS'),'Ура','Uhu', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('4',to_date('17.11.13 08:22:27','DD.MM.RR HH24:MI:SS'),'Товарищи','Buddies', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('5',to_date('16.11.13 08:22:33','DD.MM.RR HH24:MI:SS'),'Пришла','Spring', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('6',to_date('15.11.13 08:22:39','DD.MM.RR HH24:MI:SS'),'Весна','has', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('7',to_date('14.11.13 08:22:44','DD.MM.RR HH24:MI:SS'),'К','come', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('8',to_date('13.11.13 08:22:48','DD.MM.RR HH24:MI:SS'),'нам','to', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('9',to_date('12.11.13 08:22:52','DD.MM.RR HH24:MI:SS'),'в','our', null, null);
                Insert into TEST_ORACLEDB (TEST_NUMBER,TEST_DATE,TEST_NVARCHAR,TEST_VARCHAR,TEST_BLOB,TEST_CLOB) values ('10',to_date('11.11.13 08:22:57','DD.MM.RR HH24:MI:SS'),'дома','homes', null, null);";
        $db = new Db("MTK_MAIN_UNIT", "MTK_MAIN_UNIT", "ASRZ_TEST");
        try {
            $db->runScript($q)->commit();
        } catch (\Exception $e) {
            print $e->getMessage();
        }
    }

    public function testFetchArray()
    {
        $res = $this->db->query(
            "SELECT TEST_NUMBER,
                    TO_CHAR(TEST_DATE, 'DD.MM.YY HH24:MI:SS') as TEST_DATE,
                    TEST_NVARCHAR,
                    TEST_VARCHAR
             FROM TEST_ORACLEDB ORDER BY 1"
        )->fetchArray();
        $expected = [
            [ 1, '20.11.13 08:20:23', 'Ололошеньки', 'Ololo' ],
            [ 2, '19.11.13 08:21:26', 'Тынц', 'Tync' ],
            [ 3, '18.11.13 08:22:21', 'Ура', 'Uhu' ],
            [ 4, '17.11.13 08:22:27', 'Товарищи', 'Buddies' ],
            [ 5, '16.11.13 08:22:33', 'Пришла', 'Spring' ],
            [ 6, '15.11.13 08:22:39', 'Весна', 'has' ],
            [ 7, '14.11.13 08:22:44', 'К', 'come' ],
            [ 8, '13.11.13 08:22:48', 'нам', 'to' ],
            [ 9, '12.11.13 08:22:52', 'в', 'our' ],
            [ 10, '11.11.13 08:22:57', 'дома', 'homes' ]
        ];
        $this->assertEquals($expected, $res, "fetchArray should return numeric leys array");
    }

    public function testFetchAssoc()
    {
        $res = $this->db->query(
            "SELECT TEST_NUMBER,
                    TO_CHAR(TEST_DATE,'DD.MM.YY HH24:MI:SS') as TEST_DATE,
                    TEST_NVARCHAR,
                    TEST_VARCHAR
             FROM TEST_ORACLEDB ORDER BY 1"
        )->fetchAssoc();
        $expected = [
            [
                "TEST_NUMBER"   => 1,
                "TEST_DATE"     => '20.11.13 08:20:23',
                "TEST_NVARCHAR" => 'Ололошеньки',
                "TEST_VARCHAR"  => 'Ololo'
            ],
            [
                "TEST_NUMBER"   => 2,
                "TEST_DATE"     => '19.11.13 08:21:26',
                "TEST_NVARCHAR" => 'Тынц',
                "TEST_VARCHAR"  => 'Tync'
            ],
            [
                "TEST_NUMBER"   => 3,
                "TEST_DATE"     => '18.11.13 08:22:21',
                "TEST_NVARCHAR" => 'Ура',
                "TEST_VARCHAR"  => 'Uhu'
            ],
            [
                "TEST_NUMBER"   => 4,
                "TEST_DATE"     => '17.11.13 08:22:27',
                "TEST_NVARCHAR" => 'Товарищи',
                "TEST_VARCHAR"  => 'Buddies'
            ],
            [
                "TEST_NUMBER"   => 5,
                "TEST_DATE"     => '16.11.13 08:22:33',
                "TEST_NVARCHAR" => 'Пришла',
                "TEST_VARCHAR"  => 'Spring'
            ],
            [
                "TEST_NUMBER"   => 6,
                "TEST_DATE"     => '15.11.13 08:22:39',
                "TEST_NVARCHAR" => 'Весна',
                "TEST_VARCHAR"  => 'has'
            ],
            [
                "TEST_NUMBER"   => 7,
                "TEST_DATE"     => '14.11.13 08:22:44',
                "TEST_NVARCHAR" => 'К',
                "TEST_VARCHAR"  => 'come'
            ],
            [
                "TEST_NUMBER"   => 8,
                "TEST_DATE"     => '13.11.13 08:22:48',
                "TEST_NVARCHAR" => 'нам',
                "TEST_VARCHAR"  => 'to'
            ],
            [
                "TEST_NUMBER"   => 9,
                "TEST_DATE"     => '12.11.13 08:22:52',
                "TEST_NVARCHAR" => 'в',
                "TEST_VARCHAR"  => 'our'
            ],
            [
                "TEST_NUMBER"   => 10,
                "TEST_DATE"     => '11.11.13 08:22:57',
                "TEST_NVARCHAR" => 'дома',
                "TEST_VARCHAR"  => 'homes'
            ]
        ];
        $this->assertEquals($expected, $res, "fetchAssoc should return numeric leys array");
    }

    public function testFetchOne()
    {
        $q = $this->db->query(
            "SELECT TEST_NUMBER,
                    TO_CHAR(TEST_DATE,'DD.MM.YY HH24:MI:SS') as TEST_DATE,
                    TEST_NVARCHAR,
                    TEST_VARCHAR
             FROM TEST_ORACLEDB ORDER BY 1"
        );
        $res = $q->fetchValue();
        $this->assertEquals(1, $res, "fetchOne should return first column from first row without parameters");

        $res = $q->execute()->fetchValue(3);
        $this->assertEquals(
            'Ололошеньки',
            $res,
            "fetchOne should return numbered column from first row"
        );

        $res = $q->execute()->fetchValue("TEST_DATE");
        $this->assertEquals(
            '20.11.13 08:20:23',
            $res,
            "fetchOne should return named column from first row"
        );
    }

    public function testfetchObject()
    {
        $q = $this->db->query(
            "SELECT TEST_NUMBER,
                    TO_CHAR(TEST_DATE,'DD.MM.YY HH24:MI:SS') as TEST_DATE,
                    TEST_NVARCHAR,
                    TEST_VARCHAR
             FROM TEST_ORACLEDB ORDER BY 1"
        );
        $res = $q->fetchObject();
        $expected = [
            (object) [
                "TEST_NUMBER"   => 1,
                "TEST_DATE"     => '20.11.13 08:20:23',
                "TEST_NVARCHAR" => 'Ололошеньки',
                "TEST_VARCHAR"  => 'Ololo'
            ],
            (object) [
                "TEST_NUMBER"   => 2,
                "TEST_DATE"     => '19.11.13 08:21:26',
                "TEST_NVARCHAR" => 'Тынц',
                "TEST_VARCHAR"  => 'Tync'
            ],
            (object) [
                "TEST_NUMBER"   => 3,
                "TEST_DATE"     => '18.11.13 08:22:21',
                "TEST_NVARCHAR" => 'Ура',
                "TEST_VARCHAR"  => 'Uhu'
            ],
            (object) [
                "TEST_NUMBER"   => 4,
                "TEST_DATE"     => '17.11.13 08:22:27',
                "TEST_NVARCHAR" => 'Товарищи',
                "TEST_VARCHAR"  => 'Buddies'
            ],
            (object) [
                "TEST_NUMBER"   => 5,
                "TEST_DATE"     => '16.11.13 08:22:33',
                "TEST_NVARCHAR" => 'Пришла',
                "TEST_VARCHAR"  => 'Spring'
            ],
            (object) [
                "TEST_NUMBER"   => 6,
                "TEST_DATE"     => '15.11.13 08:22:39',
                "TEST_NVARCHAR" => 'Весна',
                "TEST_VARCHAR"  => 'has'
            ],
            (object) [
                "TEST_NUMBER"   => 7,
                "TEST_DATE"     => '14.11.13 08:22:44',
                "TEST_NVARCHAR" => 'К',
                "TEST_VARCHAR"  => 'come'
            ],
            (object) [
                "TEST_NUMBER"   => 8,
                "TEST_DATE"     => '13.11.13 08:22:48',
                "TEST_NVARCHAR" => 'нам',
                "TEST_VARCHAR"  => 'to'
            ],
            (object) [
                "TEST_NUMBER"   => 9,
                "TEST_DATE"     => '12.11.13 08:22:52',
                "TEST_NVARCHAR" => 'в',
                "TEST_VARCHAR"  => 'our'
            ],
            (object) [
                "TEST_NUMBER"   => 10,
                "TEST_DATE"     => '11.11.13 08:22:57',
                "TEST_NVARCHAR" => 'дома',
                "TEST_VARCHAR"  => 'homes'
            ]
        ];
        $this->assertEquals($expected, $res, "fetchOne should return first column from first row without parameters");
    }

    protected function setUp()
    {
        parent::setUp();
        $this->db = new Db("TEST", "TEST", "TEST");
    }

}
