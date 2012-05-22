<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Repositories;

use Doctrine\ORM\EntityRepository;
use Entities;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LazaretFileRepository extends EntityRepository
{

    /**
     * Returns all lazaret files for a given offset & limit
     *
     * @param   int     $offset
     * @param   int     $limit
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getFiles($offset, $limit)
    {
        $qb = $this->_em->createQueryBuilder();

        $qb
            ->add('select', 'l')
            ->add('from', 'Entities\LazaretFile l')
            ->add('orderBy', 'l.updated DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $query = $qb->getQuery();

        return $query->getResult();
    }
}
