<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Tools\Pagination\Paginator;
use PDO;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Translation\TranslatorInterface;

class BasketRepository extends EntityRepository
{
    const MYBASKETS = 'my baskets';
    const RECEIVED = 'received';
    const VALIDATION_SENT = 'validation_sent';
    const VALIDATION_DONE = 'validation_done';

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setTranslator(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    /**
     * @param string $id
     * @param array $parameters
     * @param string $domain
     * @param string $locale
     * @return string
     */
    private function trans($id, $parameters = [], $domain = null, $locale = null)
    {
        if ($this->translator) {
            return $this->translator->trans( /** @Ignore */ $id, $parameters, $domain, $locale);
        }

        return $id;
    }

    /**
     * Returns all basket for a given user that are not marked as archived
     *
     * @param User $user
     * @param null|string $sort
     * @return Basket[]
     */
    public function findActiveByUser(User $user, $sort = null)
    {
        // checked : 4 usages, "b.elements" is useless
        $dql = "SELECT b\n"
            . " FROM Phraseanet:Basket b\n"
            // . " LEFT JOIN b.elements e\n"    //
            . " WHERE b.user = :usr_id\n"
            . " AND b.archived = false";

        if ($sort == 'date') {
            $dql .= "\n ORDER BY b.created DESC";
        }
        elseif ($sort == 'name') {
            $dql .= "\n ORDER BY b.name ASC";
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['usr_id' => $user->getId()]);

        return $query->getResult();
    }

    /**
     * Returns all unread basket for a given user that are not marked as archived
     *
     * @param  User $user
     * @return Basket[]
     */
    public function findUnreadActiveByUser(User $user)
    {
        // too bad dql does not support "UNION" so we first get ids in sql...
        // grouping the 2 parts as 1 requires "LEFT JOIN"'s , it was really slow.
        $sql = "SELECT b.id\n"
            . "   FROM Baskets b\n"
            . "   WHERE b.archived = 0\n"
            . "     AND b.user_id = :usr_id\n"
            . "     AND b.is_read = 0\n"
            . " UNION\n"
            . "SELECT b.id\n"
            . " FROM Baskets b\n"
            . "  INNER JOIN BasketParticipants p ON (p.`basket_id` = b.`id`)\n"
            . " WHERE b.archived = 0\n"
            . "   AND b.user_id != :usr_id\n"
            . "   AND p.user_id = :usr_id\n"
            . "   AND p.is_aware = 0\n"
            // see truth-table in findActiveValidationByUser()
            . "   AND (\n"
            . "    b.share_expires IS NULL\n"
            . "     OR\n"
            . "    CURRENT_TIMESTAMP() < b.share_expires\n"
            . "     OR\n"
            . "    (b.vote_expires IS NOT NULL AND CURRENT_TIMESTAMP() < b.vote_expires)\n"
            . "   )";

        $params = [
            ':usr_id'       => $user->getId()
        ];

        $stmt = $this->_em->getConnection()->executeQuery($sql, $params);
        $basket_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        // ... then we fetch the basket objects in dql
        $dql = "SELECT b FROM Phraseanet:Basket b\n"
            . " WHERE b.id IN (:basket_ids)";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('basket_ids', $basket_ids);

        return $query->getResult();
    }

    /**
     * Returns all baskets that are not expired (share or vote) and
     * where a specified user is participant (not owner)
     *
     * @param  User         $user
     * @param  null|string  $sort
     * @return Basket[]
     *
     *  0, 1 or 2 dates on a timeline : V="end-of-vote" ; S="end-of-share)
     *  .: basket is visible ; _:basket no nore visible
     *
     *  ..............     // no dates : always visible
     *  ......V.......     // vote with no end-of-share : always visible
     *  ......S_______     // hidden after simple end-of-share
     *  ....V....S____     // hidden after end-of-share
     *  ....S....V____     // end-of-vote extends end-of-share
     *  ......VS______     // same date : trivial
     *
     */
    public function findActiveValidationByUser(User $user, $sort = null)
    {
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.participants p
            WHERE b.user != :usr_id AND p.user = :usr_id
             AND (
               b.share_expires IS NULL
                OR 
               CURRENT_TIMESTAMP() < b.share_expires 
                OR
               (b.vote_expires IS NOT NULL AND CURRENT_TIMESTAMP() < b.vote_expires) 
             )';

        if ($sort == 'date') {
            $dql .= "\nORDER BY b.created DESC";
        } elseif ($sort == 'name') {
            $dql .= "\nORDER BY b.name ASC";
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters([':usr_id' => $user->getId()]);

        return $query->getResult();
    }

    /**
     * Find a basket specified by his basket_id and his owner or participant
     *
     * @param int $basket_id
     * @param User $user
     * @param $requireOwner // true: the user MUST be the owner ;
     *                      // false: IF THE BASKET IS A FEEDBACK the user can also be simple participant
     * @return Basket
     * @throws NonUniqueResultException
     */
    public function findUserBasket($basket_id, User $user, $requireOwner)
    {
        // checked : 3 usages, "b.elements e" seems useless
        $dql = "SELECT b\n"
            . " FROM Phraseanet:Basket b\n"
            . " WHERE b.id = :basket_id";

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['basket_id' => $basket_id]);

        $basket = $query->getOneOrNullResult();

        if (null === $basket) {
            throw new NotFoundHttpException($this->trans('Basket is not found'));
        }

        /* @var Basket $basket */
        if ($basket->getUser()->getId() != $user->getId()) {
            $participant = false;

            if (($basket->isVoteBasket() || $basket->getParticipants()->count() > 0) && !$requireOwner) {
                try {
                    $basket->getParticipant($user);
                    $participant = true;
                }
                catch (\Exception $e) {
                    // no-op
                }
            }
            if (!$participant) {
                throw new AccessDeniedHttpException($this->trans('You have not access to this basket'));
            }
        }

        return $basket;
    }

    public function findContainingRecordForUser(\record_adapter $record, User $user)
    {
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.elements e
            LEFT JOIN b.participants p
            WHERE e.record_id = :record_id AND e.sbas_id = :databox_id
                AND (
                    b.user = :usr_id
                    OR (
                        p.user = :usr_id
                        AND (
                            b.share_expires IS NULL
                            OR 
                            CURRENT_TIMESTAMP() < b.share_expires 
                            OR
                            (b.vote_expires IS NOT NULL AND CURRENT_TIMESTAMP() < b.vote_expires)
                        )
                    )
                )
            ';

        $params = [
            'record_id' => $record->getRecordId(),
            'databox_id'=> $record->getDataboxId(),
            'usr_id'    => $user->getId(),
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    public function findWorkzoneBasket(User $user, $query, $year, $type, $offset, $perPage)
    {
        switch ($type) {
            case self::RECEIVED:
                $dql = "SELECT b\n"
                    . "FROM Phraseanet:Basket b\n"
                //    . "  JOIN b.elements e\n"
                    . "WHERE b.user = :usr_id AND b.pusher IS NOT NULL";
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            case self::VALIDATION_DONE:
                // todo : check when called, and if "LEFT JOIN b.elements e" is usefull
                $dql = "SELECT b\n"
                    . "FROM Phraseanet:Basket b\n"
                    . "  JOIN b.elements e\n"
                    . "  JOIN b.participants p\n"
                    . "WHERE b.user != :usr_id AND p.user = :usr_id";
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            case self::VALIDATION_SENT:         // we expect initiator = owner
                $dql = "SELECT b\n"
                . "FROM Phraseanet:Basket b\n"
                . "WHERE b.vote_initiator = :usr_id AND  b.user = :usr_id";
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            case self::MYBASKETS:
                $dql = "SELECT b\n"
                . "FROM Phraseanet:Basket b\n"
            //    . "LEFT JOIN b.participants p\n"
                . "WHERE (b.user = :usr_id)";
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            default:
                // todo : check when called, and if "LEFT JOIN b.elements e" is usefull
                $dql = "SELECT b\n"
                . "FROM Phraseanet:Basket b\n"
            //    . "LEFT JOIN b.elements e\n"
            //    . "LEFT JOIN b.participants p\n"
                . "WHERE (b.user = :usr_id OR b.vote_initiator = :usr_id)";     // !!!!!!!!!!! always same user ?
                $params = [
                    'usr_id'            => $user->getId()
                ];
        }

        if (ctype_digit($year) && strlen($year) == 4) {
            $dql .= "\n AND b.created >= :min_date AND b.created <= :max_date";

            $params['min_date'] = sprintf('%d-01-01 00:00:00', $year);
            $params['max_date'] = sprintf('%d-12-31 23:59:59', $year);
        }

        if (trim($query) !== '') {
            $dql .= "\n AND (b.name LIKE :name OR b.description LIKE :description)";

            $params['name'] = '%' . $query . '%';
            $params['description'] = '%' . $query . '%';
        }

        $dql .= "\n ORDER BY b.id DESC";

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params)
            ->setFirstResult($offset)
            ->setMaxResults($perPage);

        return new Paginator($query, true);
    }

    /**
     * Return all actives validation where current user is involved and user basket
     *
     * @param  User  $user
     * @param  string  $sort
     * @return Basket[]
     */
    public function findActiveValidationAndBasketByUser(User $user, $sort = null)
    {
        // todo : check caller and if "LEFT JOIN b.elements e" is usefull
//        $dql = 'SELECT b
//            FROM Phraseanet:Basket b
//            LEFT JOIN b.elements e
//            LEFT JOIN b.participants p
//            WHERE (b.user = :usr_id AND b.archived = false)
//              OR (b.user != :usr_id AND p.user = :usr_id
//                  AND (b.vote_expires IS NULL OR b.vote_expires > CURRENT_TIMESTAMP())
//                  )';

        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            LEFT JOIN b.participants p
            WHERE (b.user = :usr_id AND b.archived = false)
              OR (b.user != :usr_id AND p.user = :usr_id
                  AND (b.vote_expires IS NULL OR b.vote_expires > CURRENT_TIMESTAMP())
                  )';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['usr_id' => $user->getId()]);

        return $query->getResult();
    }
}
