<?php
/**
 * Date: 03.04.14
 * Time: 14:53
 *
 * @category 
 * @package  OracleDb
 * @author   nightlinus <user@localhost>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  
 * @link     
 */

namespace OracleDb;
use Traversable;


/**
 * Class StatementCache
 * @package OracleDb
 */
class StatementCache implements \IteratorAggregate {

    /**
     * @var Statement[];
     */
    protected $hashCache = [];

    /**
     * @var Statement[]
     */
    protected $orderCache = [];

    /**
     * @var int
     */
    protected $cacheSize = 50;

    /**
     * @return mixed
     */
    public function getCacheSize()
    {
        return $this->cacheSize;
    }

    /**
     * @param mixed $cacheSize
     *
     * @return \Generator
     */
    public function setCacheSize($cacheSize)
    {
        $this->cacheSize = $cacheSize;
        return $this->getCleanCount();
    }

    /**
     * @param $cacheSize
     */
    public function __construct($cacheSize)
    {
        $this->cacheSize = $cacheSize;
    }

    /**
     * @param $statement Statement
     *
     * @return \Generator
     */
    public function add($statement)
    {
        $hash = $this->getHash($statement);
        $this->hashCache[ $hash ]['value'] = $statement;
        $this->orderCache[] = $statement;
        $this->hashCache[ $hash ]['position'] = count($this->orderCache) - 1;
        return $this->getCleanCount();
    }

    /**
     * @param $sql string
     *
     * @return null
     */
    public function get($sql)
    {
        $hash = $this->getHash($sql);
        $inCache = isset($this->hashCache[ $hash ]['value']);
        if ($inCache) {
            $statement = $this->hashCache[ $hash ][ 'value' ];
            array_splice($this->orderCache, $this->hashCache[ $hash ][ 'position' ], 1);
            $this->orderCache[ ] = $statement;
            return $statement;
        } else {
            return null;
        }
    }

    /**
     * @return int
     */
    protected function getCleanCount()
    {
        $count = count($this->orderCache) - $this->cacheSize;

        return $count > 0 ? $count : 0;
    }

    /**
     * @param $statement
     *
     * @return mixed
     */
    public function remove($statement)
    {
        $hash = $this->getHash($statement);
        $position = $this->hashCache[$hash]['position'];
        array_splice($this->orderCache, $position, 1);
        unset($this->hashCache[ $hash ]);

        return $statement;
    }

    /**
     * @param $statement Statement|string
     *
     * @return string
     */
    protected function getHash($statement)
    {
        $sql = $statement;
        if ($statement instanceof Statement) {
            $sql = $statement->getQueryString();
        }

        return hash('md5', $sql);
    }

    /**
     * @return mixed
     */
    public function getLast()
    {
        return end($this->orderCache);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Retrieve an external iterator
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     */
    public function getIterator()
    {
        $count = count($this->orderCache);
        for( $i = 0; $i < $count; $i++) {
            yield $this->orderCache[ $i ];
        }
    }
}