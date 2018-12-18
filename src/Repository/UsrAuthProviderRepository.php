<?php

namespace App\Repository;

use App\Entity\UsrAuthProvider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\User;

/**
 * @method UsrAuthProvider|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsrAuthProvider|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsrAuthProvider[]    findAll()
 * @method UsrAuthProvider[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsrAuthProviderRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UsrAuthProvider::class);
    }

    /**
     * @param User $user
     * @return UsrAuthProvider[]
     */
    public function findByUser(User $user)
    {
        $dql = 'SELECT u
                FROM Phraseanet:UsrAuthProvider u
                WHERE u.user = :usrId';

        $params = ['usrId' => $user->getId()];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     * @param $providerId
     * @param $distantId
     * @return UsrAuthProvider|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findWithProviderAndId($providerId, $distantId)
    {
        $dql = 'SELECT u
                FROM Phraseanet:UsrAuthProvider u
                WHERE u.provider = :providerId AND u.distant_id = :distantId';

        $params = ['providerId' => $providerId, 'distantId' => $distantId];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getOneOrNullResult();
    }
}
