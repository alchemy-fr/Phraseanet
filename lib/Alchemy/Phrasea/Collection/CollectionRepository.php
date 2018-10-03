<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Collection;

interface CollectionRepository
{

    /**
     * @return \collection[]
     */
    public function findAll();

    /**
     * @param int $collectionId
     * @return \collection|null
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
