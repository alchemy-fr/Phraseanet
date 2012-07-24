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
use Doctrine\DBAL\Types\Type;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ValidationParticipantRepository extends EntityRepository
{

    /**
     * Retrieve all not reminded participants where the validation has not expired
     *
     * @param $expireDate The expiration Date
     * @return array
     */
    public function findNotConfirmedAndNotRemindedParticipantsByExpireDate(\DateTime $expireDate)
    {
        $dql = '
            SELECT p, s
            FROM Entities\ValidationParticipant p
            JOIN p.session s
            JOIN s.basket b
            WHERE p.is_confirmed = 0
            AND p.reminded IS NULL
            AND s.expires < :date';

        return $this->_em->createQuery($dql)
                ->setParameter('date', $expireDate, Type::DATETIME)
                ->getResult();
    }
}

