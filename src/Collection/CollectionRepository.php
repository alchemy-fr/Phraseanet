<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Collection;

interface CollectionRepository
{

    /**
     * @return \App\Utils\collection[]
     */
    public function findAll();

    /**
     * @param int $collectionId
     * @return \App\Utils\collection|null
     */
    public function find($collectionId);

    /**
     * @param Collection $collection
     * @return void
     */
    public function save(Collection $collection);

    /**
     * @param Collection $collection
     * @return void
     */
    public function delete(Collection $collection);
}
