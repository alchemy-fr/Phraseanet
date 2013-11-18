<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Gedmo\Timestampable\TimestampableListener;

class patch_390alpha1b implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.1';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['order'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM Orders';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM OrderElements';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT id, usr_id, created_on, `usage`, deadline, ssel_id FROM `order`';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());

        foreach ($rs as $row) {

            $sql = 'SELECT count(id) as todo FROM order_elements WHERE deny = NULL AND order_id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $row['id']]);
            $todo = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            $order = new Order();
            $order->setUsrId($row['usr_id'])
                ->setTodo($todo['todo'])
                ->setOrderUsage($row['usage'])
                ->setDeadline(new \DateTime($row['deadline']))
                ->setCreatedOn(new \DateTime($row['created_on']))
                ->setBasket($row['ssel_id']);

            $em->persist($order);

            $sql = 'SELECT base_id, record_id, order_master_id, deny
                    FROM order_elements
                    WHERE order_id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $row['id']]);
            $elements = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($elements as $element) {
                $orderElement = new OrderElement();
                $orderElement->setBaseId($element['base_id'])
                    ->setDeny($element['deny'] === null ? null : (Boolean) $element['deny'])
                    ->setOrder($order)
                    ->setOrderMasterId($element['order_master_id'])
                    ->setRecordId($element['record_id']);

                $order->addElement($orderElement);
                $em->persist($orderElement);
            }

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        $em->getEventManager()->addEventSubscriber(new TimestampableListener());

        return true;
    }
}
