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


/**
 * Class StatementCache
 * @package OracleDb
 */
class StatementCache {

    /**
     * @var Statement[];
     */
    protected $hashCache = [];

    /**
     * @var Statement[]
     */
    protected $orderCache = [];

    protected $cacheSize;

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
        return $this->cleanOldCache();
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
        return $this->cleanOldCache();
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
     * @return \Generator
     */
    protected function cleanOldCache()
    {
        while (count($this->orderCache) >= $this->cacheSize) {
            $statement = array_shift($this->orderCache);
            $hash = $this->getHash($statement);
            unset($this->hashCache[ $hash ]);
            yield $statement;
        }
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
} 