<?php
/**
 * Class that include cache functionality
 *
 * @category Database
 * @package  nightlinus\OracleDb
 * @author   nightlinus <m.a.ogarkov@gmail.com>
 * @license  http://opensource.org/licenses/MIT MIT
 * @link     https://github.com/nightlinus/OracleDb
 */

namespace nightlinus\OracleDb\Statement;

/**
 * Class StatementCache
 */
class StatementCache implements \IteratorAggregate
{

    /**
     * @var int
     */
    private $cacheSize = 50;

    /**
     * @var Statement[];
     */
    private $hashCache = [ ];

    /**
     * @var Statement[]
     */
    private $orderCache = [ ];

    /**
     * @param $cacheSize
     */
    public function __construct($cacheSize)
    {
        $this->cacheSize = $cacheSize;
    }

    public function add(Statement $statement)
    {
        $hash = $this->getHash($statement);
        $inCache = isset($this->hashCache[ $hash ][ 'value' ]);
        if (!$inCache) {
            $this->hashCache[ $hash ][ 'value' ] = $statement;
            $this->orderCache[] = $statement;
            $this->hashCache[ $hash ][ 'position' ] = count($this->orderCache) - 1;
            $this->garbageCollect();
        }
    }

    private function garbageCollect()
    {
        $iter = $this->getIterator();
        while ($this->needGarbageCollect()) {
            $trashStatement = $iter->current();
            if ($trashStatement->canBeFreed()) {
                $trashStatement->free();
            }
            $iter->next();
        }
    }

    /**
     * @param $sql string
     *
     * @return null|Statement
     */
    public function get($sql)
    {
        $hash = $this->getHash($sql);
        $inCache = isset($this->hashCache[ $hash ][ 'value' ]);
        $statement = null;
        if ($inCache) {
            $statement = $this->hashCache[ $hash ][ 'value' ];
            array_splice($this->orderCache, $this->hashCache[ $hash ][ 'position' ], 1);
            $this->orderCache[] = $statement;

            return $statement;
        }

        return $statement;
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
     */
    public function setCacheSize($cacheSize)
    {
        $this->cacheSize = $cacheSize;
        $this->garbageCollect();
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
     * @return bool
     */
    private function needGarbageCollect()
    {
        $count = count($this->orderCache) - $this->cacheSize;

        return $count > 0;
    }

    /**
     * @param $statement Statement|string
     *
     * @return string
     */
    private function getHash($statement)
    {
        $sql = $statement;
        if ($statement instanceof Statement) {
            $sql = $statement->getQueryString();
        }

        return hash('md5', $sql);
    }
}
