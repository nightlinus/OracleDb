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

use function count;

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
    private $hashCache = [];

    /**
     * @var Statement[]
     */
    private $orderCache = [];

    /**
     * @param $cacheSize
     */
    public function __construct($cacheSize)
    {
        $this->cacheSize = $cacheSize;
    }

    public function add(Statement $statement)
    {
        $hash = $this->find($statement);
        if (!$hash) {
            $hash = $this->getHash($statement);
            $this->hashCache[ $hash ][ 'value' ] = $statement;
            $this->orderCache[] = $statement;
            $this->hashCache[ $hash ][ 'position' ] = count($this->orderCache) - 1;
            $this->garbageCollect();
        }
    }

    public function clear()
    {
        $iter = $this->getIterator();
        while ($iter->valid()) {
            $trashStatement = $iter->current();
            $trashStatement->free();
            $this->remove($trashStatement);
            $iter->next();
        }
    }

    private function garbageCollect(): void
    {
        $iter = $this->getIterator();
        while ($this->needGarbageCollect() && $iter->valid()) {
            $trashStatement = $iter->current();
            if ($trashStatement->canBeFreed()) {
                $trashStatement->free();
                $this->remove($trashStatement);
            }
            $iter->next();
        }
    }

    public function get(string $sql): ?Statement
    {
        $hash = $this->find($sql);
        if ($hash) {
            $statement = $this->hashCache[ $hash ][ 'value' ];
            $this->removeFromOrderedCache($hash);
            $this->orderCache[] = $statement;
            $this->hashCache[ $hash ][ 'position' ] = count($this->orderCache) - 1;

            return $statement;
        }

        return null;
    }

    public function getCacheSize(): int
    {
        return $this->cacheSize;
    }

    public function setCacheSize(int $cacheSize): void
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
        yield from $this->orderCache;
    }

    /**
     * @param $statement Statement|string
     *
     * @return bool
     */
    public function remove($statement): bool
    {
        $hash = $this->find($statement);
        if (!$hash) {
            return false;
        }
        $this->removeFromOrderedCache($hash);
        unset($this->hashCache[ $hash ]);

        return true;
    }

    private function needGarbageCollect(): bool
    {
        $count = count($this->orderCache) - $this->cacheSize;

        return $count > 0;
    }

    /**
     * @param $statement Statement|string
     *
     * @return string
     */
    private function getHash($statement): string
    {
        $sql = $statement;
        if ($statement instanceof Statement) {
            $sql = $statement->getQueryString();
        }

        return hash('md5', $sql);
    }

    /**
     * @param string|Statement $statement
     *
     * @return null|string
     */
    private function find($statement): ?string
    {
        $hash = $this->getHash($statement);
        $inCache = isset($this->hashCache[ $hash ][ 'value' ]);

        return $inCache ? $hash : null;
    }

    private function removeFromOrderedCache(string $hash): void
    {
        array_splice($this->orderCache, $this->hashCache[ $hash ][ 'position' ], 1);
    }

    public function __destruct()
    {
        $this->clear();
    }
}
