<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Repositories;

use Doctrine\ORM\EntityRepository;
use Entities;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class BasketRepository extends EntityRepository
{
    const MYBASKETS = 'my baskets';
    const RECEIVED = 'received';
    const VALIDATION_SENT = 'validation_sent';
    const VALIDATION_DONE = 'validation_done';

    /**
     * Returns all basket for a given user that are not marked as archived
     *
     * @param  \User_Adapter                                $user
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findActiveByUser(\User_Adapter $user, $sort = null)
    {
        $dql = 'SELECT b
            FROM Entities\Basket b
            LEFT JOIN b.elements e
            WHERE b.usr_id = :usr_id
            AND b.archived = false';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(array('usr_id' => $user->get_id()));

        return $query->getResult();
    }

    /**
     * Returns all unread basket for a given user that are not marked as archived
     *
     * @param  \User_Adapter                                $user
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findUnreadActiveByUser(\User_Adapter $user)
    {
        $dql = 'SELECT b
            FROM Entities\Basket b
            JOIN b.elements e
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE b.archived = false
            AND (
              (b.usr_id = :usr_id_owner AND b.is_read = false)
              OR (b.usr_id != :usr_id_ownertwo
                  AND p.usr_id = :usr_id_participant
                  AND p.is_aware = false)
              )
            AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP())';

        $params = array(
            'usr_id_owner'       => $user->get_id(),
            'usr_id_ownertwo'    => $user->get_id(),
            'usr_id_participant' => $user->get_id()
        );

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     * Returns all baskets that are in validation session not expired  and
     * where a specified user is participant (not owner)
     *
     * @param  \User_Adapter                                $user
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findActiveValidationByUser(\User_Adapter $user, $sort = null)
    {
        $dql = 'SELECT b
            FROM Entities\Basket b
            JOIN b.elements e
            JOIN e.validation_datas v
            JOIN b.validation s
            JOIN s.participants p
            WHERE b.usr_id != ?1 AND p.usr_id = ?2
             AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP()) ';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(array(1 => $user->get_id(), 2 => $user->get_id()));

        return $query->getResult();
    }

    /**
     * Find a basket specified by his basket_id and his owner
     *
     * @throws \Exception_NotFound
     * @throws \Exception_Forbidden
     * @param  type                 $basket_id
     * @param  \User_Adapter        $user
     * @return \Entities\Basket
     */
    public function findUserBasket($basket_id, \User_Adapter $user, $requireOwner)
    {
        $dql = 'SELECT b
            FROM Entities\Basket b
            LEFT JOIN b.elements e
            WHERE b.id = :basket_id';

        $query = $this->_em->createQuery($dql);
        $query->setParameters(array('basket_id' => $basket_id));

        $basket = $query->getOneOrNullResult();

        /* @var $basket \Entities\Basket */
        if (null === $basket) {
            throw new \Exception_NotFound(_('Basket is not found'));
        }

        if ($basket->getOwner()->get_id() != $user->get_id()) {
            $participant = false;

            if ($basket->getValidation() && ! $requireOwner) {
                try {
                    $basket->getValidation()->getParticipant($user);
                    $participant = true;
                } catch (\Exception $e) {

                }
            }
            if ( ! $participant) {
                throw new \Exception_Forbidden(_('You have not access to this basket'));
            }
        }

        return $basket;
    }

    public function findContainingRecordForUser(\record_adapter $record, \User_Adapter $user)
    {

        $dql = 'SELECT b
            FROM Entities\Basket b
            JOIN b.elements e
            WHERE e.record_id = :record_id AND e.sbas_id = e.sbas_id
              AND b.usr_id = :usr_id';

        $params = array(
            'record_id' => $record->get_record_id(),
            'usr_id'    => $user->get_id()
        );

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    public function findWorkzoneBasket(\User_Adapter $user, $query, $year, $type, $offset, $perPage)
    {
        $params = array();

        switch ($type) {
            case self::RECEIVED:
                $dql = 'SELECT b
                FROM Entities\Basket b
                JOIN b.elements e
                WHERE b.usr_id = :usr_id AND b.pusher_id IS NOT NULL';
                $params = array(
                    'usr_id' => $user->get_id()
                );
                break;
            case self::VALIDATION_DONE:
                $dql = 'SELECT b
                FROM Entities\Basket b
                JOIN b.elements e
                JOIN b.validation s
                JOIN s.participants p
                WHERE b.usr_id != ?1 AND p.usr_id = ?2';
                $params = array(
                    1       => $user->get_id()
                    , 2       => $user->get_id()
                );
                break;
            case self::VALIDATION_SENT:
                $dql = 'SELECT b
                FROM Entities\Basket b
                JOIN b.elements e
                JOIN b.validation v
                WHERE b.usr_id = :usr_id';
                $params = array(
                    'usr_id' => $user->get_id()
                );
                break;
            default:
                $dql = 'SELECT b
                FROM Entities\Basket b
                LEFT JOIN b.elements e
                LEFT JOIN b.validation s
                LEFT JOIN s.participants p
                WHERE (b.usr_id = :usr_id OR p.usr_id = :validating_usr_id)';
                $params = array(
                    'usr_id'            => $user->get_id(),
                    'validating_usr_id' => $user->get_id()
                );
                break;
            case self::MYBASKETS:
                $dql = 'SELECT b
                FROM Entities\Basket b
                LEFT JOIN b.elements e
                LEFT JOIN b.validation s
                LEFT JOIN s.participants p
                WHERE (b.usr_id = :usr_id)';
                $params = array(
                    'usr_id' => $user->get_id()
                );
                break;
        }

        if (ctype_digit($year) && strlen($year) == 4) {
            $dql .= ' AND b.created >= :min_date AND b.created <= :max_date ';

            $params['min_date'] = sprintf('%d-01-01 00:00:00', $year);
            $params['max_date'] = sprintf('%d-12-31 23:59:59', $year);
        }

        if (trim($query) !== '') {
            $dql .= ' AND (b.name LIKE :name OR b.description LIKE :description) ';

            $params['name'] = '%' . $query . '%';
            $params['description'] = '%' . $query . '%';
        }

        $dql .= ' ORDER BY b.id DESC';

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params)
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        $paginator = new \Doctrine\ORM\Tools\Pagination\Paginator($query, true);

        return $paginator;
    }

    /**
     * Return all actives validation where current user is involved and user basket
     *
     * @param  \User_Adapter $user
     * @param  type          $sort
     * @return Array
     */
    public function findActiveValidationAndBasketByUser(\User_Adapter $user, $sort = null)
    {
        $dql = 'SELECT b
            FROM Entities\Basket b
            LEFT JOIN b.elements e
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE (b.usr_id = :usr_id AND b.archived = false)
              OR (b.usr_id != :usr_id AND p.usr_id = :usr_id
                  AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP())
                  )';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(array('usr_id' => $user->get_id()));

        return $query->getResult();
    }
}
