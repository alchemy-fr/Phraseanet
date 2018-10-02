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

interface CollectionReferenceRepository
{
    /**
     * @return CollectionReference[]
     */
    public function findAll();

    /**
     * @param int $databoxId
     * @return CollectionReference[]
     */
    public function findAllByDatabox($databoxId);

    /**
     * @param int $baseId
     * @return CollectionReference|null
     */
    public function find($baseId);

    /**
     * @param int[] $baseIds
     * @return CollectionReference[]
     */
    public function findMany(array $baseIds);

    /**
     * @param int $databoxId
     * @param int $collectionId
     * @return CollectionReference|null
     */
    public function findByCollectionId($databoxId, $collectionId);

    /**
     * Find Collection references having at least one Order Master
     *
     * @param array<int>|null $baseIdsSubset Restrict search to a subset of base ids.
     * @return CollectionReference[]
     */
    public function findHavingOrderMaster(array $baseIdsSubset = null);

    /**
     * @param CollectionReference $reference
     * @return void
     */
    public function save(CollectionReference $reference);

    /**
     * @param CollectionReference $reference
     * @return void
     */
    public function delete(CollectionReference $reference);
}
