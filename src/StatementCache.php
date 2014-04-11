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
        $this->hashCache[ $hash ] = $statement;
        array_push($this->orderCache, $statement);
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

        return isset($this->hashCache[ $hash ]) ? $this->hashCache[ $hash ] : null;
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
} 