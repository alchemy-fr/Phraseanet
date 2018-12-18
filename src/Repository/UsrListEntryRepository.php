<?php

namespace App\Repository;

use App\Entity\UsrListEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use App\Entity\User;
use App\Entity\UsrList;
use Doctrine\ORM\NoResultException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @method UsrListEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method UsrListEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method UsrListEntry[]    findAll()
 * @method UsrListEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UsrListEntryRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UsrListEntry::class);
    }

    /**
     * Get all lists entries matching a given User
     *
     * @param User $user
     * @param type $like
     */
    public function findUserList(User $user)
    {
        $dql = 'SELECT e FROM Phraseanet:UsrListEntry e
            WHERE e.user = :usr_id';

        $params = [
            'usr_id' => $user->getId(),
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    public function findEntryByListAndEntryId(UsrList $list, $entry_id)
    {
        $entry = $this->find($entry_id);

        if (!$entry) {
            throw new NotFoundHttpException('Entry not found');
        }

        /* @var $entry UsrListEntry */
        if ($entry->getList()->getId() != $list->getId()) {
            throw new AccessDeniedHttpException('Entry mismatch list');
        }

        return $entry;
    }

    public function findEntryByListAndUsrId(UsrList $list, $usr_id)
    {
        $dql = 'SELECT e FROM Phraseanet:UsrListEntry e
              JOIN e.list l
            WHERE e.user = :usr_id AND l.id = :list_id';

        $params = [
            'usr_id'  => $usr_id,
            'list_id' => $list->getId(),
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        try {
            $entry = $query->getSingleResult();
        } catch (NoResultException $e) {
            throw new NotFoundHttpException('Entry not found', null, $e);
        }

        return $entry;
    }
}
