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

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Entities\EntityInterface;

/**
 * This class is responsible of handling logic and complex stuff before the 
 * entity is persisted by the ORM.
 */
interface ManagerInterface
{
    /**
     * Returns an empty instance of the managed entity.
     * 
     * @return EntityInterface
     */
    public function create();
    
    /**
     * Updates the given entity.
     *
     * @param EntityInterface $entity
     * @param boolean $flush Whether to flush the changes or not (default true).
     * 
     * @throws InvalidArgumentException if provided entity is not the good type.
     */
    public function update(EntityInterface $entity, $flush = true);

    /**
     * Deletes the given entity.
     *
     * @param EntityInterface $entity
     * @param boolean $flush Whether to flush the changes or not (default true).
     * 
     * @throws InvalidArgumentException if provided entity is not the good type.
     */
    public function delete(EntityInterface $entity, $flush = true);
}
