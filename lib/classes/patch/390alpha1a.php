<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Doctrine\ORM\Query;
use Doctrine\ORM\NoResultException;
use Gedmo\Timestampable\TimestampableListener;

class patch_390alpha1a extends patchAbstract
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
        return false;
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
        return ['20131118000009', '20131118000008'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM Orders';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM OrderElements';
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app->getApplicationBox()->get_connection();
        $sql = 'SELECT id, usr_id, created_on, `usage`, deadline, ssel_id FROM `order`';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['orm.em'];
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());

        foreach ($rs as $row) {
            $sql = "SELECT count(id) as todo FROM order_elements WHERE deny = NULL AND order_id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $row['id']]);
            $todo = $stmt->fetch(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            if (null === $user = $this->loadUser($app['orm.em'], $row['usr_id'])) {
                continue;
            }

            try {
                $basket = $app['orm.em']->createQuery('SELECT PARTIAL b.{id} FROM Phraseanet:Basket b WHERE b.id = :id')
                    ->setParameters(['id' => $row['ssel_id']])
                    ->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true)
                    ->getSingleResult();
            } catch (NoResultException $e) {
                continue;
            }

            $order = new Order();
            $order->setUser($user)
                ->setTodo($todo['todo'])
                ->setOrderUsage($row['usage'])
                ->setDeadline(new \DateTime($row['deadline']))
                ->setCreatedOn(new \DateTime($row['created_on']))
                ->setBasket($basket);

            $em->persist($order);

            $sql = "SELECT base_id, record_id, order_master_id, deny FROM order_elements WHERE order_id = :id";

            $stmt = $conn->prepare($sql);
            $stmt->execute([':id' => $row['id']]);
            $elements = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            foreach ($elements as $element) {
                $orderElement = new OrderElement();
                $user = $this->loadUser($app['orm.em'], $row['usr_id']);
                $orderElement->setBaseId($element['base_id'])
                    ->setDeny($element['deny'] === null ? null : (Boolean) $element['deny'])
                    ->setOrder($order)
                    ->setOrderMaster($user)
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
