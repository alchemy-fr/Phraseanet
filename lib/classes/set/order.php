<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\RecordsRequest;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class set_order extends set_abstract
{
    /**
     *
     * @var int
     */
    protected $id;

    /**
     *
     * @var user
     */
    protected $user;

    /**
     *
     * @var int
     */
    protected $todo;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var string
     */
    protected $usage;

    /**
     *
     * @var DateTime
     */
    protected $deadline;

    /**
     *
     * @var int
     */
    protected $total;

    /**
     *
     * @var int
     */
    protected $ssel_id;
    protected $app;

    /**
     * Create a new order entry
     *
     * @param Application    $app
     * @param RecordsRequest $records
     * @param \User_Adapter  $orderer
     * @param string         $usage
     * @param \DateTime      $deadline
     *
     * @return boolean
     */
    public static function create(Application $app, RecordsRequest $records, \User_Adapter $orderer, $usage, \DateTime $deadline = null)
    {
        $app['phraseanet.appbox']->get_connection()->beginTransaction();

        try {
            $sql = 'INSERT INTO `order` (`id`, `usr_id`, `created_on`, `usage`, `deadline`)
            VALUES (null, :from_usr_id, NOW(), :usage, :deadline)';

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);

            $stmt->execute(array(
                ':from_usr_id' => $orderer->get_id(),
                ':usage'       => $usage,
                ':deadline'    => (null !== $deadline ? $app['date-formatter']->format_mysql($deadline) : $deadline)
            ));

            $stmt->closeCursor();

            $orderId = $app['phraseanet.appbox']->get_connection()->lastInsertId();

            $sql = 'INSERT INTO order_elements (id, order_id, base_id, record_id, order_master_id)
            VALUES (null, :order_id, :base_id, :record_id, null)';

            $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);

            foreach ($records as $record) {
                $stmt->execute(array(
                    ':order_id'  => $orderId,
                    ':base_id'   => $record->get_base_id(),
                    ':record_id' => $record->get_record_id()
                ));
            }

            $stmt->closeCursor();
            $app['phraseanet.appbox']->get_connection()->commit();
        } catch (Exception $e) {
            $app['phraseanet.appbox']->get_connection()->rollBack();

            return null;
        }

        $app['events-manager']->trigger('__NEW_ORDER__', array(
            'order_id' => $orderId,
            'usr_id'   => $orderer->get_id()
        ));

        return new static($app, $orderId);
    }

    /**
     * List orders
     *
     * @param Application $app
     * @param array       $baseIds
     * @param integer     $offsetStart
     * @param integer     $perPage
     * @param string      $sort
     *
     * @return array
     */
    public static function listOrders(Application $app, array $baseIds, $offsetStart = 0, $perPage = 10, $sort = null)
    {

        $sql = 'SELECT distinct o.id, o.usr_id, created_on, deadline, `usage`
        FROM (`order_elements` e, `order` o)
        WHERE e.base_id IN (' . implode(', ', $baseIds) . ')
        AND e.order_id = o.id
        GROUP BY o.id
        ORDER BY o.id DESC
        LIMIT ' . $offsetStart . ',' . $perPage;

        $elements = array();

        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            $id = (int) $row['id'];
            $elements[$id] = new static($app, $id);
        }

        unset($stmt);

        if ($sort && count($elements) > 0) {
            if ($sort == 'created_on') {
                uasort($elements, array(__CLASS__, 'date_orders_sort'));
            } elseif ($sort == 'user') {
                uasort($elements, array(__CLASS__, 'user_orders_sort'));
            } elseif ($sort == 'usage') {
                uasort($elements, array(__CLASS__, 'usage_orders_sort'));
            }
        }

        return $elements;
    }

    /**
     * Get total orders for selected base ids
     *
     * @param  appbox  $appbox
     * @param  array   $baseIds
     * @return integer
     */
    public static function countTotalOrder(appbox $appbox, array $baseIds = array())
    {
        $sql = 'SELECT distinct o.id
        FROM (`order_elements` e, `order` o)
        WHERE ' . (count($baseIds > 0 ) ? 'e.base_id IN (' . implode(', ', $baseIds) . ') AND ': '' ).
        'e.order_id = o.id
        GROUP BY o.id
        ORDER BY o.id DESC';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $total = $stmt->rowCount();
        $stmt->closeCursor();
        unset($stmt);

        return (int) $total;
    }

    /**
     *
     * @param Application $app
     * @param int         $id
     *
     * @return set_order
     */
    public function __construct(Application $app, $id)
    {
        $this->app = $app;
        $conn = $app['phraseanet.appbox']->get_connection();

        $sql = 'SELECT o.id, o.usr_id, o.created_on, o.`usage`, o.deadline,
              COUNT(e.id) as total, o.ssel_id, COUNT(e2.id) as todo
              FROM (`order` o, order_elements e)
              LEFT JOIN order_elements e2
                ON (
                      ISNULL(e2.order_master_id)
                      AND e.id = e2.id
                   )
              WHERE o.id = e.order_id
              AND o.id = :order_id';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':order_id' => $id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_NotFound('unknown order ' . $id);

        $current_user = User_Adapter::getInstance($row['usr_id'], $app);

        $this->id = $row['id'];
        $this->user = $current_user;
        $this->todo = $row['todo'];
        $this->created_on = new DateTime($row['created_on']);
        $this->usage = $row['usage'];
        $this->deadline = new DateTime($row['deadline']);
        $this->total = (int) $row['total'];
        $this->ssel_id = (int) $row['ssel_id'];

        $base_ids = array_keys($app['phraseanet.user']->ACL()->get_granted_base(array('order_master')));

        $sql = 'SELECT e.base_id, e.record_id, e.order_master_id, e.id, e.deny
              FROM order_elements e
              WHERE order_id = :order_id
              AND e.base_id
              IN (' . implode(',', $base_ids) . ')';

        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':order_id' => $id));
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $elements = array();

        foreach ($rs as $row) {
            $order_master_id = $row['order_master_id'] ? $row['order_master_id'] : false;

            $elements[$row['id']] = new record_orderElement(
                    $app,
                    phrasea::sbasFromBas($this->app, $row['base_id']),
                    $row['record_id'],
                    $row['deny'],
                    $order_master_id
            );
        }

        $this->elements = $elements;

        return $this;
    }

    /**
     *
     * @return User_Adapter
     */
    public function get_user()
    {
        return $this->user;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     *
     * @return DateTime
     */
    public function get_deadline()
    {
        return $this->deadline;
    }

    /**
     *
     * @return string
     */
    public function get_usage()
    {
        return $this->usage;
    }

    /**
     *
     * @return int
     */
    public function get_total()
    {
        return $this->total;
    }

    /**
     *
     * @return int
     */
    public function get_order_id()
    {
        return $this->id;
    }

    /**
     *
     * @return int
     */
    public function get_todo()
    {
        return $this->todo;
    }

    /**
     *
     * @param Application $app
     * @param Array       $elements_ids
     * @param boolean     $force
     *
     * @return set_order
     */
    public function send_elements(Application $app, Array $elements_ids, $force)
    {
        $conn = $app['phraseanet.appbox']->get_connection();

        $basrecs = array();
        foreach ($elements_ids as $id) {
            if (isset($this->elements[$id])) {
                $basrecs[$id] = array(
                    'base_id'   => $this->elements[$id]->get_base_id(),
                    'record_id' => $this->elements[$id]->get_record_id()
                );
            }
        }

        $dest_user = $this->user;

        $Basket = null;
        /* @var $repository \Repositories\BasketRepository */
        if ($this->ssel_id) {
            $repository = $app['EM']->getRepository('\Entities\Basket');

            try {
                $Basket = $repository->findUserBasket($app, $this->ssel_id, $dest_user, false);
            } catch (\Exception $e) {
                $Basket = null;
            }
        }

        if (! $Basket) {
            $Basket = new \Entities\Basket();
            $Basket->setName(sprintf(_('Commande du %s'), $this->created_on->format('Y-m-d')));
            $Basket->setOwner($this->user);
            $Basket->setPusher($app['phraseanet.user']);

            $app['EM']->persist($Basket);
            $app['EM']->flush();

            $this->ssel_id = $Basket->getId();

            $sql = 'UPDATE `order` SET ssel_id = :ssel_id WHERE id = :order_id';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':ssel_id'  => $Basket->getId(), ':order_id' => $this->id));
            $stmt->closeCursor();
        }

        $n = 0;

        $sql = 'UPDATE order_elements
              SET deny="0", order_master_id = :usr_id
              WHERE order_id = :order_id
                AND id = :order_element_id';

        if (! $force) {
            $sql .= ' AND ISNULL(order_master_id)';
        }

        $stmt = $conn->prepare($sql);

        foreach ($basrecs as $order_element_id => $basrec) {
            try {
                $sbas_id = phrasea::sbasFromBas($app, $basrec['base_id']);
                $record = new record_adapter($app, $sbas_id, $basrec['record_id']);

                $BasketElement = new \Entities\BasketElement();
                $BasketElement->setRecord($record);
                $BasketElement->setBasket($Basket);

                $Basket->addBasketElement($BasketElement);

                $app['EM']->persist($BasketElement);

                $params = array(
                    ':usr_id'           => $app['phraseanet.user']->get_id()
                    , ':order_id'         => $this->id
                    , ':order_element_id' => $order_element_id
                );

                $stmt->execute($params);

                $n ++;
                $this->user->ACL()->grant_hd_on($record, $app['phraseanet.user'], 'order');

                unset($record);
            } catch (Exception $e) {

            }
        }

        $app['EM']->flush();
        $stmt->closeCursor();

        if ($n > 0) {
            $params = array(
                'ssel_id' => $this->ssel_id,
                'from'    => $app['phraseanet.user']->get_id(),
                'to'      => $this->user->get_id(),
                'n'       => $n
            );

            $app['events-manager']->trigger('__ORDER_DELIVER__', $params);
        }

        return $this;
    }

    /**
     *
     * @param  Array     $elements_ids
     * @return set_order
     */
    public function deny_elements(Array $elements_ids)
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();

        $n = 0;

        foreach ($elements_ids as $order_element_id) {
            $sql = 'UPDATE order_elements
                SET deny="1", order_master_id = :order_master_id
                WHERE order_id = :order_id AND id = :order_element_id
                AND ISNULL(order_master_id)';

            $params = array(
                ':order_master_id'  => $this->app['phraseanet.user']->get_id()
                , ':order_id'         => $this->id
                , ':order_element_id' => $order_element_id
            );
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $stmt->closeCursor();
            $n ++;
        }

        if ($n > 0) {
            $params = array(
                'from' => $this->app['phraseanet.user']->get_id(),
                'to'   => $this->user->get_id(),
                'n'    => $n
            );

            $this->app['events-manager']->trigger('__ORDER_NOT_DELIVERED__', $params);
        }

        return $this;
    }

    /**
     * Order orders by usage
     *
     * @param  string $a
     * @param  string $b
     * @return int
     */
    private static function usage_orders_sort($a, $b)
    {
        $comp = strcasecmp($a['usage'], $b['usage']);

        if ($comp == 0) {
            return 0;
        }

        return $comp < 0 ? -1 : 1;
    }

    /**
     * Order orders by user
     *
     * @param  string $a
     * @param  string $b
     * @return int
     */
    private static function user_orders_sort($a, $b)
    {
        $comp = strcasecmp($a['usr_display'], $b['usr_display']);

        if ($comp == 0) {
            return 0;
        }

        return $comp < 0 ? -1 : 1;
    }

    /**
     * Order orders by date
     *
     * @param  DateTime $a
     * @param  DateTime $b
     * @return int
     */
    private static function date_orders_sort(DateTime $a, DateTime $b)
    {
        $comp = $b->format('U') - $a->format('U');

        if ($comp == 0) {
            return 0;
        }

        return $comp < 0 ? -1 : 1;
    }
}
