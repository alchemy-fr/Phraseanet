<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Core\Configuration\AccessRestriction;

class AccessibleDataboxIterator implements DataboxIterator
{

    /**
     * @var DataboxRepository
     */
    private $databoxRepository;

    /**
     * @var AccessRestriction
     */
    private $accessRestriction;

    /**
     * @var \databox[]
     */
    private $databoxes;

    /**
     * @param DataboxRepository $databoxRepository
     * @param AccessRestriction $accessRestriction
     */
    public function __construct(DataboxRepository $databoxRepository, AccessRestriction $accessRestriction)
    {
        $this->accessRestriction = $accessRestriction;
        $this->databoxRepository = $databoxRepository;
    }

    private function initialize()
    {
        if ($this->databoxes !== null) {
            return;
        }

        $this->databoxes = $this->accessRestriction->filterAvailableDataboxes($this->databoxRepository->findAll());
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->initialize();

        next($this->databoxes);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        $this->initialize();

        return key($this->databoxes);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     */
    public function valid()
    {
        $this->initialize();

        return $this->key() !== null && isset($this->databoxes[$this->key()]);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        $this->initialize();

        reset($this->databoxes);
    }

    /**
     * @return \databox
     */
    public function current()
    {
        $this->initialize();

        return current($this->databoxes);
    }
}
