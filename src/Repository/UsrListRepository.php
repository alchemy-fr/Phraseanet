<?php

namespace App\Repository;

use App\Entity\UsrList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method UsrList|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsrList|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsrList[]    findAll()
 * @method UsrList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsrListRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UsrList::class);
    }

    /**
     * Get all lists readable for a given User
     *
     * @param  User                                         $user
     * @return UsrList[]
     */
    public function findUserLists(User $user)
    {
        $dql = 'SELECT l FROM Phraseanet:UsrList l
              JOIN l.owners o
            WHERE o.user = :usr_id';

        $params = [
            'usr_id' => $user->getId(),
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     *
     * @param  User    $user
     * @param  int     $list_id
     * @return UsrList
     */
    public function findUserListByUserAndId(User $user, $list_id)
    {
        $list = $this->find($list_id);

        /* @var $list UsrList */
        if (null === $list) {
            throw new NotFoundHttpException('List is not found.');
        }

        if (!$list->hasAccess($user)) {
            throw new AccessDeniedHttpException('You have not access to this list.');
        }

        return $list;
    }

    /**
     * Search for a UsrList like '' with a given value, for a user
     *
     * @param  User                                         $user
     * @param  type                                         $name
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findUserListLike(User $user, $name)
    {
        $dql = 'SELECT l FROM Phraseanet:UsrList l
              JOIN l.owners o
            WHERE o.user = :usr_id AND l.name LIKE :name';

        $params = [
            'usr_id' => $user->getId(),
            'name'   => $name . '%'
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }
}
