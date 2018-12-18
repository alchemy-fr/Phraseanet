<?php

namespace App\Repository;

use App\Entity\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\Basket;
use App\Entity\User;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;

/**
 * @method Token|null find($id, $lockMode = null, $lockVersion = null)
 * @method Token|null findOneBy(array $criteria, array $orderBy = null)
 * @method Token[]    findAll()
 * @method Token[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Token::class);
    }

    /**
     * @param Basket $basket
     * @param User   $user
     * @return Token|null
     * @throws \Doctrine\ORM\NonUniqueResultException
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
     * @throws \Doctrine\ORM\NonUniqueResultException
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
    public function findExpiredTokens()
    {
        $dql = 'SELECT t FROM Phraseanet:Token t
                WHERE t.expiration < :date';

        $query = $this->_em->createQuery($dql);
        $query->setParameters([':date' => new \DateTime()]);

        return $query->getResult();
    }
}
