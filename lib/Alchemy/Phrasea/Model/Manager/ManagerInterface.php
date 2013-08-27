<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manager;

/**
 * This class is responsible of handling logic and complex stuff before the 
 * entity is persisted by the ORM.
 */
interface ManagerInterface
{
    /**
     * Returns an empty instance of the managed entity.
     */
    public function create();
    
    /**
     * Updates the given entity.
     *
     * @param $entity
     * @param boolean $flush Whether to flush the changes or not (default true).
     */
    public function update($entity, $flush = true);

    /**
     * Deletes the given entity.
     *
     * @param User $entity
     * @param boolean $flush Whether to flush the changes or not (default true).
     */
    public function delete($entity, $flush = true);
}
