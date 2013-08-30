<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Doctrine\Common\Collections\ArrayCollection;
use Entities\Basket;
use Entities\BasketElement;
use Entities\Order as OrderEntity;
use Entities\OrderElement;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Order implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
            $app['firewall']->requireAuthentication()
                ->requireRight('order');
        });

        /**
         * List all orders
         *
         * name         : prod_orders
         *
         * description  : Display all orders
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/', $this->call('displayOrders'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_orders');

        /**
         * Create a new order
         *
         * name         : prod_order_new
         *
         * description  : Create a new order
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/', $this->call('createOrder'))
            ->bind('prod_order_new');

        /**
         * Display one order
         *
         * name         : prod_order
         *
         * description  : Display one order
         *
         * method       : GET
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->get('/{order_id}/', $this->call('displayOneOrder'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order')
            ->assert('order_id', '\d+');

        /**
         * Send a new order
         *
         * name         : prod_order_send
         *
         * description  : Send an order
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/{order_id}/send/', $this->call('sendOrder'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order_send')
            ->assert('order_id', '\d+');

        /**
         * Deny an order
         *
         * name         : prod_order_deny
         *
         * description  : Deny an order
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response | JSON Response
         */
        $controllers->post('/{order_id}/deny/', $this->call('denyOrder'))
            ->before(function(Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order_deny')
            ->assert('order_id', '\d+');

        return $controllers;
    }

    /**
     * Create a new order
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return RedirectResponse|JsonResponse
     */
    public function createOrder(Application $app, Request $request)
    {
        $success = false;
        $collectionHasOrderAdmins = new ArrayCollection();
        $toRemove = array();

        $records = RecordsRequest::fromRequest($app, $request, true, array('cancmd'));
        $hasOneAdmin = array();

        if (!$records->isEmpty()) {
            $order = new OrderEntity();
            $order->setUsrId($app['authentication']->getUser()->get_id());
            $order->setDeadline((null !== $deadLine = $request->request->get('deadline')) ? new \DateTime($deadLine) : $deadLine);
            $order->setOrderUsage($request->request->get('use', ''));
            foreach ($records as $key => $record) {
                if ($collectionHasOrderAdmins->containsKey($record->get_base_id())) {
                    if (!$collectionHasOrderAdmins->get($record->get_base_id())) {
                        $records->remove($key);
                    }
                }

                if (!isset($hasOneAdmin[$record->get_base_id()])) {
                    $query = new \User_Query($app);
                    $hasOneAdmin[$record->get_base_id()] = (Boolean) count($query->on_base_ids(array($record->get_base_id()))
                        ->who_have_right(array('order_master'))
                        ->execute()->get_results());
                }

                $collectionHasOrderAdmins->set($record->get_base_id(), $hasOneAdmin[$record->get_base_id()]);

                if (!$hasOneAdmin[$record->get_base_id()]) {
                    $toRemove[] = $key;
                } else {
                    $orderElement = new OrderElement();
                    $order->addElement($orderElement);
                    $orderElement->setOrder($order);
                    $orderElement->setBaseId($record->get_base_id());
                    $orderElement->setRecordId($record->get_record_id());
                    $app['EM']->persist($orderElement);
                }
            }

            foreach ($toRemove as $key) {
                if ($records->containsKey($key)) {
                    $records->remove($key);
                }
            }

            $noAdmins = $collectionHasOrderAdmins->forAll(function($key, $hasAdmin) {
                    return false === $hasAdmin;
                });

            if ($noAdmins) {
                $msg = _('There is no one to validate orders, please contact an administrator');
            }

            $order->setTodo($order->getElements()->count());

            try {
                $app['events-manager']->trigger('__NEW_ORDER__', array(
                    'order_id' => $order->getId(),
                    'usr_id'   => $order->getUsrId()
                ));
                $success = true;

                $app['EM']->persist($order);
                $app['EM']->flush();
            } catch (\Exception $e) {

            }

            if ($success) {
                $msg = _('The records have been properly ordered');
            } else {
                $msg = _('An error occured');
            }
        } else {
            $msg = _('There is no record eligible for an order');
        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success' => $success,
                'msg'     => $msg,
            ));
        }

        return $app->redirectPath('prod_orders', array(
            'success' => (int) $success,
            'action'  => 'send'
        ));
    }

    /**
     * Display list of orders
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return Response
     */
    public function displayOrders(Application $app, Request $request)
    {
        $page = (int) $request->query->get('page', 1);
        $offsetStart = $page - 1;
        $perPage = (int) $request->query->get('per-page', 10);
        $sort = $request->query->get('sort');

        $baseIds = array_keys($app['authentication']->getUser()->ACL()->get_granted_base(array('order_master')));

        $ordersList = $app['EM']->getRepository('Entities\Order')->listOrders($baseIds, $offsetStart, $perPage, $sort);
        $total = $app['EM']->getRepository('Entities\Order')->countTotalOrders($baseIds);

        return $app['twig']->render('prod/orders/order_box.html.twig', array(
            'page'         => $page,
            'perPage'      => $perPage,
            'total'        => $total,
            'previousPage' => $page < 2 ? false : ($page - 1),
            'nextPage'     => $page >= ceil($total / $perPage) ? false : $page + 1,
            'orders'       => new ArrayCollection($ordersList)
        ));
    }

    /**
     * Display a single order identified by its id
     *
     * @param  Application $app
     * @param  Request     $request
     * @param  integer     $order_id
     * @return Response
     */
    public function displayOneOrder(Application $app, Request $request, $order_id)
    {
        $order = $app['EM']->getRepository('Entities\Order')->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        return $app['twig']->render('prod/orders/order_item.html.twig', array(
            'order' => $order
        ));
    }

    /**
     * Send an order
     *
     * @param  Application                   $app
     * @param  Request                       $request
     * @param  integer                       $order_id
     * @return RedirectResponse|JsonResponse
     */
    public function sendOrder(Application $app, Request $request, $order_id)
    {
        $success = false;
        $order = $app['EM']->getRepository('Entities\Order')->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        $dest_user = \User_Adapter::getInstance($order->getUsrId(), $app);

        $basket = $order->getBasket();

        if (null === $basket) {
            $basket = new Basket();
            $basket->setName(sprintf(_('Commande du %s'), $order->getCreatedOn()->format('Y-m-d')));
            $basket->setOwner($dest_user);
            $basket->setPusher($app['authentication']->getUser());

            $app['EM']->persist($basket);
            $app['EM']->flush();
        }

        $n = 0;
        $elements = $request->request->get('elements', array());
        foreach ($order->getElements() as $orderElement) {
            if (in_array($orderElement->getId(), $elements)) {
                $sbas_id = \phrasea::sbasFromBas($app, $orderElement->getBaseId());
                $record = new \record_adapter($app, $sbas_id, $orderElement->getRecordId());

                $basketElement = new BasketElement();
                $basketElement->setRecord($record);
                $basketElement->setBasket($basket);

                $orderElement->setOrderMasterId($app['authentication']->getUser()->get_id());
                $orderElement->setDenied(false);
                $orderElement->getOrder()->setBasket($basket);

                $basket->addElement($basketElement);

                $n++;
                $dest_user->ACL()->grant_hd_on($record, $app['authentication']->getUser(), 'order');
            }
        }

        try {
            if ($n > 0) {
                $order->setTodo($order->getTodo() - $n);

                $app['events-manager']->trigger('__ORDER_DELIVER__', array(
                    'ssel_id' => $order->getBasket()->getId(),
                    'from'    => $app['authentication']->getUser()->get_id(),
                    'to'      => $dest_user->get_id(),
                    'n'       => $n
                ));
            }
            $success = true;

            $app['EM']->persist($basket);
            $app['EM']->persist($orderElement);
            $app['EM']->persist($order);
            $app['EM']->flush();
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success'  => $success,
                'msg'      => $success ? _('Order has been sent') : _('An error occured while sending, please retry  or contact an admin if problem persists'),
                'order_id' => $order_id
            ));
        }

        return $app->redirectPath('prod_orders', array(
            'success' => (int) $success,
            'action'  => 'send'
        ));
    }

    /**
     * Deny an order
     *
     * @param  Application                   $app
     * @param  Request                       $request
     * @param  integer                       $order_id
     * @return RedirectResponse|JsonResponse
     */
    public function denyOrder(Application $app, Request $request, $order_id)
    {
        $success = false;
        $order = $app['EM']->getRepository('Entities\Order')->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        $n = 0;
        $elements = $request->request->get('elements', array());
        foreach ($order->getElements() as $orderElement) {
            if (in_array($orderElement->getId(),$elements)) {
                $orderElement->setOrderMasterId($app['authentication']->getUser()->get_id());
                $orderElement->setDenied(true);

                $app['EM']->persist($orderElement);
                $n++;
            }
        }

        try {
            if ($n > 0) {
                $order->setTodo($order->getTodo() - $n);

                $app['events-manager']->trigger('__ORDER_NOT_DELIVERED__', array(
                    'from' => $app['authentication']->getUser()->get_id(),
                    'to'   => $order->getUsrId(),
                    'n'    => $n
                ));
            }
            $success = true;

            $app['EM']->persist($order);
            $app['EM']->flush();
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json(array(
                'success'  => $success,
                'msg'      => $success ? _('Order has been denied') : _('An error occured while denying, please retry  or contact an admin if problem persists'),
                'order_id' => $order_id
            ));
        }

        return $app->redirectPath('prod_orders', array(
            'success' => (int) $success,
            'action'  => 'send'
        ));
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
