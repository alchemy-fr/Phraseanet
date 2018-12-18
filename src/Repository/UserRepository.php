<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }


    /**
     * Finds admins.
     *
     * @return User[]
     */
    public function findAdmins()
    {
        $qb = $this->createQueryBuilder('u');

        $qb->where($qb->expr()->eq('u.admin', $qb->expr()->literal(true)))
            ->andWhere($qb->expr()->isNull('u.templateOwner'))
            ->andWhere($qb->expr()->eq('u.deleted', $qb->expr()->literal(false)));

        return $qb->getQuery()->getResult();
    }

    /**
     * Finds a user by login.
     *
     * @param string $login
     *
     * @return null|User
     */
    public function findByLogin($login)
    {
        return $this->findOneBy(['login' => $login]);
    }

    /**
     * Finds deleted users.
     *
     * @return User[]
     */
    public function findDeleted()
    {
        return $this->findBy(['deleted' => true]);
    }

    /**
     * Finds a user by email.
     * nb : mail match is CASE INSENSITIVE, "john@doe"=="John@Doe"=="john@DOE"
     *
     * @param string $email
     *
     * @return null|User
     */
    public function findByEmail($email)
    {
        $qb = $this->createQueryBuilder('u');

        $qb->where($qb->expr()->eq($qb->expr()->lower('u.email'), $qb->expr()->lower($qb->expr()->literal($email))))
            ->andWhere($qb->expr()->isNotNull('u.email'))
            ->andWhere($qb->expr()->eq('u.deleted', $qb->expr()->literal(false)));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds a user that is not deleted, not a model and not a guest.
     * nb : login match is CASE INSENSITIVE, "doe"=="Doe"=="DOE"
     *
     * @param $login
     *
     * @return null|User
     */
    public function findRealUserByLogin($login)
    {
        $qb = $this->createQueryBuilder('u');
        $qb->where($qb->expr()->eq($qb->expr()->lower('u.login'), $qb->expr()->lower($qb->expr()->literal($login))))
            ->andWhere($qb->expr()->isNotNull('u.email'))
            ->andWhere($qb->expr()->isNull('u.templateOwner'))
            ->andWhere($qb->expr()->eq('u.guest', $qb->expr()->literal(false)))
            ->andWhere($qb->expr()->eq('u.deleted', $qb->expr()->literal(false)));

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Finds templates owned by a given user.
     *
     * @param User $user
     *
     * @return array
     */
    public function findTemplateOwner(User $user)
    {
        return $this->findBy(['templateOwner' => $user->getId()]);
    }
}
