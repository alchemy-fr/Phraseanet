<?php

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use DateTime;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;

/**
 * TokenRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TokenRepository extends EntityRepository
{
    /**
     * @param Basket $basket
     * @param User   $user
     * @return Token|null
     * @throws NonUniqueResultException
     */
    public function findValidationToken(Basket $basket, User $user)
    {
        $dql = 'SELECT t FROM Phraseanet:Token t
            WHERE t.type = :type
                AND t.user = :user
                AND t.data = :basket_id
                AND (t.expiration > CURRENT_TIMESTAMP() OR t.expiration IS NULL) ORDER BY t.created DESC';

        $query = $this->_em->createQuery($dql);
        $query->setMaxResults(1);
        $query->setParameters([
            ':type' => TokenManipulator::TYPE_VALIDATE,
            ':user' => $user,
            ':basket_id' => $basket->getId(),
        ]);

        return $query->getOneOrNullResult();
    }

    /**
     * @param string $value
     * @return Token|null
     * @throws NonUniqueResultException
     */
    public function findValidToken($value)
    {
        $dql = 'SELECT t FROM Phraseanet:Token t
                WHERE t.value = :value
                    AND (t.expiration IS NULL OR t.expiration >= CURRENT_TIMESTAMP()) ORDER BY t.created DESC';

        $query = $this->_em->createQuery($dql);
        $query->setMaxResults(1);
        $query->setParameters([':value' => $value]);

        return $query->getOneOrNullResult();
    }

    /**
     * @return Token[]
     */
    public function findExpiredTokens($nbDaysAfterExpiration = 0)
    {
        $dql = 'SELECT t FROM Phraseanet:Token t
                WHERE t.expiration < :date';

        $query = $this->_em->createQuery($dql);
        $date = new DateTime();

        if ($nbDaysAfterExpiration != 0) {
            $date->modify("-" . $nbDaysAfterExpiration . " day");
        }

        $query->setParameters([':date' => $date]);

        return $query->getResult();
    }

    public function getEntityManager()
    {
        return parent::getEntityManager();
    }
}
