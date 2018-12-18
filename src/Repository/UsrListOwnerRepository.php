<?php

namespace App\Repository;

use App\Entity\UsrListOwner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\UsrList;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method UsrListOwner|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsrListOwner|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsrListOwner[]    findAll()
 * @method UsrListOwner[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsrListOwnerRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UsrListOwner::class);
    }

    /**
     *
     *
     * @param  UsrList $list
     * @param  type    $owner_id
     * @return UsrList
     */
    public function findByListAndOwner(UsrList $list, $owner_id)
    {
        $owner = $this->find($owner_id);

        /* @var $owner UsrListOwner */
        if (null === $owner) {
            throw new NotFoundHttpException('Owner is not found');
        }

        if ( ! $owner->getList()->getid() != $list->getId()) {
            throw new AccessDeniedHttpException('Owner and list mismatch');
        }

        return $owner;
    }

    /**
     *
     *
     * @param  UsrList $list
     * @param  type    $usr_id
     * @return UsrList
     */
    public function findByListAndUsrId(UsrList $list, $usr_id)
    {
        $dql = 'SELECT o FROM Phraseanet:UsrListOwner o
              JOIN o.list l
            WHERE l.id = :list_id AND o.user = :usr_id';

        $params = [
            'usr_id'  => $usr_id,
            'list_id' => $list->getId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        try {
            $owner = $query->getSingleResult();
        } catch (NoResultException $e) {
            throw new NotFoundHttpException('Owner is not found', null, $e);
        }

        return $owner;
    }
}
