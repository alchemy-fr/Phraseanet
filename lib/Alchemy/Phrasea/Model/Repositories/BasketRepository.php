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
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter findUnreadActiveByUser(usr_id=%s)", $user->getId())
        ), FILE_APPEND | LOCK_EX);


        // too bad dql does not support "UNION" so we first get ids in sql...
        // grouping the 2 parts as 1 requires "LEFT JOIN"'s , it was really slow.
        $sql = "SELECT b.id\n"
            . "   FROM Baskets b\n"
            . "   WHERE b.archived = 0\n"
            . "     AND b.user_id = :usr_id_owner\n"
            . "     AND b.is_read = 0\n"
            . " UNION\n"
            . "SELECT b.id\n"
            . " FROM Baskets b\n"
            . "  INNER JOIN ValidationSessions s\n"
            . "  INNER JOIN ValidationParticipants p\n"
            . " WHERE b.archived = 0\n"
            . "   AND b.user_id != :usr_id_ownertwo\n"
            . "   AND p.user_id = :usr_id_participant\n"
            . "   AND p.is_aware = 0\n"
            . "   AND s.expires > CURRENT_TIMESTAMP()";

        $params = [
            'usr_id_owner'       => $user->getId(),
            'usr_id_ownertwo'    => $user->getId(),
            'usr_id_participant' => $user->getId()
        ];

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("fetching basket id's in sql")
        ), FILE_APPEND | LOCK_EX);

        $stmt = $this->_em->getConnection()->executeQuery($sql, $params);
        $basket_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $stmt->closeCursor();

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("fetched %s basket id's, fetching baskets id dql", count($basket_ids))
        ), FILE_APPEND | LOCK_EX);

        // ... then we fetch the basket objects in dql
        $dql = "SELECT b FROM Phraseanet:Basket b\n"
            . " WHERE b.id IN (:basket_ids)";

        $query = $this->_em->createQuery($dql);
        $query->setParameter('basket_ids', $basket_ids);

        $result = $query->getResult();

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("fetched baskets, return from findUnreadActiveByUser(...)")
        ), FILE_APPEND | LOCK_EX);

        return $result;
    }

    /**
     * Returns all baskets that are in validation session not expired  and
     * where a specified user is participant (not owner)
     *
     * @param  User         $user
     * @param  null|string  $sort
     * @return Basket[]
     */
    public function findActiveValidationByUser(User $user, $sort = null)
    {
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter findActiveValidationByUser(usr_id=%s)", $user->getId())
        ), FILE_APPEND | LOCK_EX);

        // checked : 2 usages, "b.elements" seems useless.
        $dql = "SELECT b\n"
            . "FROM Phraseanet:Basket b\n"
            // . "  JOIN b.elements e\n"
            // . "  JOIN e.validation_datas v\n"
            . "  JOIN b.validation s\n"
            . "  JOIN s.participants p\n"
            . "WHERE b.user != ?1 AND p.user = ?2\n"
            . "  AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP())";

        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.elements e
            JOIN e.validation_datas v
            JOIN b.validation s
            JOIN s.participants p
            WHERE b.user != ?1 AND p.user = ?2
             AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP()) ';

        if ($sort == 'date') {
            $dql .= "\nORDER BY b.created DESC";
        } elseif ($sort == 'name') {
            $dql .= "\nORDER BY b.name ASC";
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters([1 => $user->getId(), 2 => $user->getId()]);

        $result = $query->getResult();

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from findActiveValidationByUser(...)")
        ), FILE_APPEND | LOCK_EX);

        return $result;
    }

    /**
     * Find a basket specified by his basket_id and his owner
     *
     * @throws NotFoundHttpException
     * @throws AccessDeniedHttpException
     * @param  int $basket_id
     * @param  User $user
     * @return Basket
     */
    public function findUserBasket($basket_id, User $user, $requireOwner)
    {
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter findUserBasket(basket_id=%s, usr_id=%s)", $basket_id, $user->getId())
        ), FILE_APPEND | LOCK_EX);

        // checked : 3 usages, "b.elements e" seems useless
        $dql = "SELECT b\n"
            . " FROM Phraseanet:Basket b\n"
            // . " LEFT JOIN b.elements e\n"
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

            if ($basket->getValidation() && !$requireOwner) {
                try {
                    $basket->getValidation()->getParticipant($user);
                    $participant = true;
                } catch (\Exception $e) {

                }
            }
            if (!$participant) {
                throw new AccessDeniedHttpException($this->trans('You have not access to this basket'));
            }
        }

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from findUserBasket(...)")
        ), FILE_APPEND | LOCK_EX);

        return $basket;
    }

    public function findContainingRecordForUser(\record_adapter $record, User $user)
    {
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter findContainingRecordForUser(record_id=%s, usr_id=%s)", $record->getId(), $user->getId())
        ), FILE_APPEND | LOCK_EX);

        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.elements e
            WHERE e.record_id = :record_id AND e.sbas_id = :databox_id
              AND b.user = :usr_id';

        $params = [
            'record_id' => $record->getRecordId(),
            'databox_id'=> $record->getDataboxId(),
            'usr_id'    => $user->getId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        $result = $query->getResult();

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from findContainingRecordForUser(...)")
        ), FILE_APPEND | LOCK_EX);

        return $result;
    }

    public function findWorkzoneBasket(User $user, $query, $year, $type, $offset, $perPage)
    {
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter findWorkzoneBasket(usr_id=%s)", $user->getId())
        ), FILE_APPEND | LOCK_EX);

        switch ($type) {
            case self::RECEIVED:
                // todo : check when called, and if "LEFT JOIN b.elements e" is usefull
                $dql = "SELECT b\n"
                    . "FROM Phraseanet:Basket b\n"
                    . "  JOIN b.elements e\n"
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
                    . "  JOIN b.validation s\n"
                    . "  JOIN s.participants p\n"
                    . "WHERE b.user != ?1 AND p.user = ?2";
                $params = [
                    1 => $user->getId(),
                    2 => $user->getId()
                ];
                break;
            case self::VALIDATION_SENT:
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                JOIN b.validation v
                WHERE b.user = :usr_id';
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            case self::MYBASKETS:
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                LEFT JOIN b.validation s
                LEFT JOIN s.participants p
                WHERE (b.user = :usr_id)';
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            default:
                // todo : check when called, and if "LEFT JOIN b.elements e" is usefull
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                LEFT JOIN b.elements e
                LEFT JOIN b.validation s
                LEFT JOIN s.participants p
                WHERE (b.user = :usr_id OR p.user = :validating_usr_id)';
                $params = [
                    'usr_id'            => $user->getId(),
                    'validating_usr_id' => $user->getId()
                ];
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

        $ret = new Paginator($query, true);

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from findWorkzoneBasket(...)")
        ), FILE_APPEND | LOCK_EX);

        return $ret;
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
        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("enter findActiveValidationAndBasketByUser(usr_id=%s)", $user->getId())
        ), FILE_APPEND | LOCK_EX);

        // todo : check caller and if "LEFT JOIN b.elements e" is usefull
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            LEFT JOIN b.elements e
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE (b.user = :usr_id AND b.archived = false)
              OR (b.user != :usr_id AND p.user = :usr_id
                  AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP())
                  )';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['usr_id' => $user->getId()]);

        $result = $query->getResult();

        file_put_contents(dirname(__FILE__).'/../../../../../logs/notifications.txt', sprintf("%s [%s] : %s (%s); %s\n", (date('Y-m-d\TH:i:s')), getmypid(), __FILE__, __LINE__,
            sprintf("return from findActiveValidationAndBasketByUser(...)")
        ), FILE_APPEND | LOCK_EX);

        return $result;
    }
}
