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

use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Parameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BasketElementRepository extends EntityRepository
{
    /**
     * @param int $element_id
     * @param User $user
     * @return BasketElement
     */
    public function findUserElement($element_id, User $user)
    {
        $dql = <<<'DQL'
SELECT e
FROM Phraseanet:BasketElement e
JOIN e.basket b
LEFT JOIN b.participants p WITH p.user = :usr_id
LEFT JOIN e.votes vd WITH vd.participant = :usr_id
WHERE (b.user = :usr_id OR p.user = :usr_id)
    AND e.id = :element_id
DQL;

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'usr_id' => $user->getId(),
            'element_id' => $element_id,
        ]);

        $element = $query->getOneOrNullResult();

        if (null === $element) {
            throw new NotFoundHttpException('Element was not found');
        }

        return $element;
    }

    /**
     * @param \record_adapter $record
     * @return BasketElement[]
     */
    public function findElementsByRecord(\record_adapter $record)
    {
        $dql = <<<'DQL'
SELECT e
FROM Phraseanet:BasketElement e
JOIN e.basket b
LEFT JOIN b.participants p
WHERE e.record_id = :record_id
AND e.sbas_id = :sbas_id
DQL;

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'sbas_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
        ]);

        return $query->getResult();
    }

    /**
     * @param \databox $databox
     * @return BasketElement[]
     */
    public function findElementsByDatabox(\databox $databox)
    {
        $dql = <<<'DQL'
SELECT e
FROM Phraseanet:BasketElement e
JOIN e.basket b
LEFT JOIN b.participants p
WHERE e.sbas_id = :sbas_id
DQL;

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'sbas_id' => $databox->get_sbas_id(),
        ]);

        return $query->getResult();
    }

    public function findElementsDatasByRecord(\record_adapter $record)
    {
        $dql = <<<'DQL'
SELECT be
FROM Phraseanet:BasketElement AS be
INNER JOIN be.basket b
INNER JOIN b.participants AS vp
INNER JOIN vp.votes AS vd 
WHERE be.record_id = :record_id
AND be.sbas_id = :sbas_id
ORDER BY b.created DESC
DQL;

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'sbas_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
        ]);

        return $query->getResult();
    }

    /**
     * @param  \record_adapter $record
     * @param  User $user
     * @return BasketElement[]
     */
    public function findReceivedElementsByRecord(\record_adapter $record, User $user)
    {
        $dql = <<<'DQL'
SELECT e
FROM Phraseanet:BasketElement e
JOIN e.basket b
LEFT JOIN b.participants p
WHERE b.user = :usr_id
AND b.pusher IS NOT NULL
AND e.record_id = :record_id
AND e.sbas_id = :sbas_id
DQL;

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'sbas_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'usr_id' => $user->getId(),
        ]);

        return $query->getResult();
    }

    /**
     * @param \record_adapter $record
     * @param User $user
     * @return BasketElement[]
     */
    public function findReceivedValidationElementsByRecord(\record_adapter $record, User $user)
    {
        $dql = <<<'DQL'
SELECT e
FROM Phraseanet:BasketElement e
JOIN e.basket b
JOIN b.participants p
WHERE p.user = :usr_id
AND e.record_id = :record_id
AND e.sbas_id = :sbas_id
DQL;

        $query = $this->_em->createQuery($dql);
        $query->setParameters([
            'sbas_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'usr_id' => $user->getId(),
        ]);

        return $query->getResult();
    }

    /**
     * @param array $records Each record is an array which MUST have a databox_id AND record_id key
     * @param null|int $basketId
     * @return BasketElement[]
     */
    public function findByRecords(array $records, $basketId = null)
    {
        $perDataboxLookup = [];

        foreach ($records as $record) {
            if (!isset($record['databox_id'], $record['record_id'])) {
                throw new \LogicException('Each record should have a databox_id AND record_id key');
            }

            $databoxId = $record['databox_id'];
            $recordId = $record['record_id'];

            if (!isset($perDataboxLookup[$databoxId])) {
                $perDataboxLookup[$databoxId] = [];
            }
            $perDataboxLookup[$databoxId][] = $recordId;
        }

        if (!$perDataboxLookup) {
            return [];
        }

        $builder = $this->createQueryBuilder('e');

        $parameters = new ArrayCollection();

        if ($basketId) {
            $builder->where('e.basket = :basket_id');
            $parameters->add(new Parameter('basket_id', $basketId, \PDO::PARAM_INT));
        }

        $parameterGroup = 1;
        $expr = $builder->expr()->orX();
        foreach ($perDataboxLookup as $databoxId => $recordsIds) {
            $databoxIdParameter = sprintf('databoxId%d', $parameterGroup);
            $recordIdsParameter = sprintf('recordIds%d', $parameterGroup);

            $expr->add($builder->expr()->andX(
                sprintf('e.sbas_id = :%s', $databoxIdParameter),
                sprintf('e.record_id IN (:%s)', $recordIdsParameter)
            ));

            $parameters->add(new Parameter($databoxIdParameter, $databoxId, \PDO::PARAM_INT));
            $parameters->add(new Parameter($recordIdsParameter, $recordsIds, Connection::PARAM_INT_ARRAY));

            ++$parameterGroup;
        }

        $builder->andWhere($expr);
        $builder->setParameters($parameters);

        return $builder->getQuery()->getResult();
    }

    public function getElements(User $user, $databoxId = null, $recordId = null, $nbElement = 200)
    {
        $qb = $this->createQueryBuilder('be');
        $qb->innerJoin('be.basket', 'b');

        $qb->where($qb->expr()->eq('b.user', ':user'));
        $qb->setParameter(':user', $user);

        if ($databoxId != null) {
            $qb->andWhere('be.sbas_id = :databoxId');
            $qb->setParameter(':databoxId', $databoxId);
        }

        if ($recordId != null) {
            $qb->andWhere('be.record_id = :recordId');
            $qb->setParameter(':recordId', $recordId);
        }

        $qb->orderBy('be.id', 'DESC');
        $qb->setMaxResults($nbElement);

        return $qb->getQuery()->getResult();
    }

    public function getElementsCount(User $user, $databoxId = null, $recordId = null)
    {
        $qb = $this->createQueryBuilder('be');
        $qb->select('count(be)');
        $qb->innerJoin('be.basket', 'b');

        $qb->where($qb->expr()->eq('b.user', ':user'));
        $qb->setParameter(':user', $user);

        if ($databoxId != null) {
            $qb->andWhere('be.sbas_id = :databoxId');
            $qb->setParameter(':databoxId', $databoxId);
        }

        if ($recordId != null) {
            $qb->andWhere('be.record_id = :recordId');
            $qb->setParameter(':recordId', $recordId);
        }

        return $qb->getQuery()->getSingleScalarResult();
    }
}
