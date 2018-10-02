<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Doctrine\ORM\EntityRepository;
use Doctrine\DBAL\Types\Type;

class ValidationParticipantRepository extends EntityRepository
{

    /**
     * Retrieve all not reminded participants where the validation has not expired
     *
     * @param $expireDate The expiration Date
     * @return ValidationParticipant[]
     */
    public function findNotConfirmedAndNotRemindedParticipantsByExpireDate(\DateTime $expireDate)
    {
        $dql = '
            SELECT p, s
            FROM Phraseanet:ValidationParticipant p
            JOIN p.session s
            JOIN s.basket b
            WHERE p.is_confirmed = 0
            AND p.reminded IS NULL
            AND s.expires < :date AND s.expires > CURRENT_TIMESTAMP()';

        return $this->_em->createQuery($dql)
                ->setParameter('date', $expireDate, Type::DATETIME)
                ->getResult();
    }
}
