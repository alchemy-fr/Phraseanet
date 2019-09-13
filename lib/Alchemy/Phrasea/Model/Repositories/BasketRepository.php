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
            return $this->translator->trans($id, $parameters, $domain, $locale);
        }

        return $id;
    }

    /**
     * Returns all basket for a given user that are not marked as archived
     *
     * @param  User $user
     * @return Basket[]
     */
    public function findActiveByUser(User $user, $sort = null)
    {
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            LEFT JOIN b.elements e
            WHERE b.user = :usr_id
            AND b.archived = false';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters(['usr_id' => $user->getId()]);

        return $query->getResult();
    }

    /**
     * Returns all unread basket for a given user that are not marked as archived
     *
     * @param  User                                         $user
     * @return Basket[]
     */
    public function findUnreadActiveByUser(User $user)
    {
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.elements e
            LEFT JOIN b.validation s
            LEFT JOIN s.participants p
            WHERE b.archived = false
            AND (
              (b.user = :usr_id_owner AND b.isRead = false)
              OR (b.user != :usr_id_ownertwo
                  AND p.user = :usr_id_participant
                  AND p.is_aware = false)
              )
            AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP())';

        $params = [
            'usr_id_owner'       => $user->getId(),
            'usr_id_ownertwo'    => $user->getId(),
            'usr_id_participant' => $user->getId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    /**
     * Returns all baskets that are in validation session not expired  and
     * where a specified user is participant (not owner)
     *
     * @param  User                                         $user
     * @return Basket[]
     */
    public function findActiveValidationByUser(User $user, $sort = null)
    {
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.elements e
            JOIN e.validation_datas v
            JOIN b.validation s
            JOIN s.participants p
            WHERE b.user != ?1 AND p.user = ?2
             AND (s.expires IS NULL OR s.expires > CURRENT_TIMESTAMP()) ';

        if ($sort == 'date') {
            $dql .= ' ORDER BY b.created DESC';
        } elseif ($sort == 'name') {
            $dql .= ' ORDER BY b.name ASC';
        }

        $query = $this->_em->createQuery($dql);
        $query->setParameters([1 => $user->getId(), 2 => $user->getId()]);

        return $query->getResult();
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
        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            LEFT JOIN b.elements e
            WHERE b.id = :basket_id';

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

        return $basket;
    }

    public function findContainingRecordForUser(\record_adapter $record, User $user)
    {

        $dql = 'SELECT b
            FROM Phraseanet:Basket b
            JOIN b.elements e
            WHERE e.record_id = :record_id AND e.sbas_id = e.sbas_id
              AND b.user = :usr_id';

        $params = [
            'record_id' => $record->getRecordId(),
            'usr_id'    => $user->getId()
        ];

        $query = $this->_em->createQuery($dql);
        $query->setParameters($params);

        return $query->getResult();
    }

    public function findWorkzoneBasket(User $user, $query, $year, $type, $offset, $perPage)
    {
        switch ($type) {
            case self::RECEIVED:
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                JOIN b.elements e';
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            case self::VALIDATION_DONE:
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                JOIN b.elements e
                JOIN b.validation s
                JOIN s.participants p
                WHERE b.user != ?1 AND p.user = ?2';
                $params = [
                    1       => $user->getId()
                    , 2       => $user->getId()
                ];
                break;
            case self::VALIDATION_SENT:
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                JOIN b.elements e
                JOIN b.validation v
                WHERE b.user = :usr_id';
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            case self::MYBASKETS:
                $dql = 'SELECT b
                FROM Phraseanet:Basket b
                LEFT JOIN b.elements e
                LEFT JOIN b.validation s
                LEFT JOIN s.participants p
                WHERE (b.user = :usr_id)';
                $params = [
                    'usr_id' => $user->getId()
                ];
                break;
            default:
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

        return $query->getResult();
    }
}
