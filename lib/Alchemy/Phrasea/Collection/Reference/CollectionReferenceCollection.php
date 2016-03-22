<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection\Reference;

use Assert\Assertion;

class CollectionReferenceCollection implements \IteratorAggregate
{
    /**
     * @var CollectionReference[]
     */
    private $references;

    /**
     * @param CollectionReference[] $references
     */
    public function __construct($references)
    {
        Assertion::allIsInstanceOf($references, CollectionReference::class);
        $this->references = $references instanceof \Traversable ? iterator_to_array($references) : $references;
    }

    /**
     * Returns an array of array with actual index as leaf value.
     *
     * @return array<int,array<int,mixed>>
     */
    public function groupByDataboxIdAndCollectionId()
    {
        $groups = [];

        foreach ($this->references as $index => $reference) {
            $databoxId = $reference->getDataboxId();
            $group = isset($groups[$databoxId]) ? $groups[$databoxId] : [];

            $group[$reference->getCollectionId()] = $index;
            $groups[$databoxId] = $group;
        }

        return $groups;
    }

    /**
     * @return \ArrayIterator|CollectionReference[]
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->references);
    }
}
