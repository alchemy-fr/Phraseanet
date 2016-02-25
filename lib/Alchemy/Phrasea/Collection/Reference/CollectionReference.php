<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection\Reference;

class CollectionReference 
{
    /**
     * @var int
     */
    private $baseId;

    /**
     * @var int
     */
    private $databoxId;

    /**
     * @var int
     */
    private $collectionId;

    /**
     * @var int
     */
    private $displayIndex;

    /**
     * @var bool
     */
    private $isActive;

    /**
     * @var string
     */
    private $alias;

    /**
     * @param int $baseId
     * @param int $databoxId
     * @param int $collectionId
     * @param int $displayIndex
     * @param bool $isActive
     * @param string $alias
     */
    public function __construct($baseId, $databoxId, $collectionId, $displayIndex, $isActive, $alias)
    {
        $this->baseId = (int) $baseId;
        $this->databoxId = (int) $databoxId;
        $this->collectionId = (int) $collectionId;
        $this->displayIndex = (int) $displayIndex;
        $this->isActive = (bool) $isActive;
        $this->alias = (string) $alias;
    }

    /**
     * @return int
     */
    public function getDataboxId()
    {
        return $this->databoxId;
    }

    /**
     * @return int
     */
    public function getBaseId()
    {
        return $this->baseId;
    }

    /**
     * @param int $baseId
     */
    public function setBaseId($baseId)
    {
        if ($this->baseId > 0) {
            throw new \LogicException('Cannot change the baseId of an existing collection reference.');
        }

        $this->baseId = $baseId;
    }

    /**
     * @return int
     */
    public function getCollectionId()
    {
        return $this->collectionId;
    }

    /**
     * @return int
     */
    public function getDisplayIndex()
    {
        return $this->displayIndex;
    }

    /**
     * @param int $index
     * @return $this
     */
    public function setDisplayIndex($index)
    {
        $this->displayIndex = (int) $index;

        return $this;
    }

    /**
     * @return boolean
     */
    public function isActive()
    {
        return $this->isActive;
    }

    /**
     * @return $this
     */
    public function disable()
    {
        $this->isActive = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function enable()
    {
        $this->isActive = true;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return $this
     */
    public function setAlias($alias)
    {
        $this->alias = (string) $alias;

        return $this;
    }
}
