<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Doctrine\ORM\EntityRepository;

/**
 * This class is responsible of manipulating entities.
 */
interface ManipulatorInterface
{
    /**
     * Returns the entity repository.
     * 
     * @return EntityRepository
     */
    public function getRepository();
}
