<?php
/**
 * Class that include cache functionality
 *
 * PHP version 5.5
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @version  0.1.0
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb;

/**
 * Class StatementCache
 *
 * @package nightlinus\OracleDb
 */
class StatementCache implements \IteratorAggregate
{

    /**
     * @type int
     */
    protected $cacheSize = 50;

    /**
     * @type Statement[];
     */
    protected $hashCache = [ ];

    /**
     * @type Statement[]
     */
    protected $orderCache = [ ];

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
        $toClean = 0;
        $inCache = isset($this->hashCache[ $hash ][ 'value' ]);
        if (!$inCache) {
            $this->hashCache[ $hash ][ 'value' ] = $statement;
            $this->orderCache[ ] = $statement;
            $this->hashCache[ $hash ][ 'position' ] = count($this->orderCache) - 1;
            $toClean = $this->getCleanCount();
        }

        return $toClean;
    }

    /**
     * @param $sql string
     *
     * @return null
     */
    public function get($sql)
    {
        $hash = $this->getHash($sql);
        $inCache = isset($this->hashCache[ $hash ][ 'value' ]);
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
     * Retrieve an external iterator
     *
     * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return \Iterator
     */
    public function getIterator()
    {
        $count = count($this->orderCache);
        for ($i = 0; $i < $count; $i++) {
            yield $this->orderCache[ $i ];
        }
    }

    /**
     * @return mixed
     */
    public function getLast()
    {
        return end($this->orderCache);
    }

    /**
     * @param $statement
     *
     * @return mixed
     */
    public function remove($statement)
    {
        $hash = $this->getHash($statement);
        $position = $this->hashCache[ $hash ][ 'position' ];
        array_splice($this->orderCache, $position, 1);
        unset($this->hashCache[ $hash ]);

        return $statement;
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
}
