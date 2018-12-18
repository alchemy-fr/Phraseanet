<?php

namespace App\Repository;

use App\Entity\ValidationParticipant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Doctrine\DBAL\Types\Type;

/**
 * @method ValidationParticipant|null find($id, $lockMode = null, $lockVersion = null)
 * @method ValidationParticipant|null findOneBy(array $criteria, array $orderBy = null)
 * @method ValidationParticipant[]    findAll()
 * @method ValidationParticipant[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ValidationParticipantRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ValidationParticipant::class);
    }

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
