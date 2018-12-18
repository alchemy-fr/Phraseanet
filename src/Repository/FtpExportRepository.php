<?php

namespace App\Repository;

use App\Entity\FtpExport;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method FtpExport|null find($id, $lockMode = null, $lockVersion = null)
 * @method FtpExport|null findOneBy(array $criteria, array $orderBy = null)
 * @method FtpExport[]    findAll()
 * @method FtpExport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FtpExportRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, FtpExport::class);
    }

    /**
     * Returns exports that crashed. If a date is provided, only exports created
     * before this date are returned.
     *
     * @param \DateTime $before An optional date to search
     *
     * @return array
     */
    public function findCrashedExports(\DateTime $before = null)
    {
        $qb = $this->createQueryBuilder('e');
        $qb->where($qb->expr()->gte('e.crash', 'e.nbretry'));

        if (null !== $before) {
            $qb->andWhere($qb->expr()->lte('e.created', ':created'));
            $qb->setParameter(':created', $before);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Returns a list of exports that can be achieved.
     *
     * @return array
     */
    public function findDoableExports()
    {
        $dql = 'SELECT f
            FROM Phraseanet:FtpExport f
                INNER JOIN f.elements e
            WHERE e.done = false';

        $query = $this->_em->createQuery($dql);

        return $query->getResult();
    }

    /**
     * Returns the exports initiated by a given user.
     *
     * @param User $user
     *
     * @return array
     */
    public function findByUser(User $user)
    {
        return $this->findBy(['user' => $user]);
    }
}
