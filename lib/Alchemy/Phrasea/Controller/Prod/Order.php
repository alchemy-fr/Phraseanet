<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Controller\RecordsRequest;
use Doctrine\Common\Collections\ArrayCollection;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order as OrderEntity;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Order implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.order'] = $this;

        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireRight('order');
        });

        $controllers->get('/', 'controller.prod.order:displayOrders')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_orders');

        $controllers->post('/', 'controller.prod.order:createOrder')
            ->bind('prod_order_new');

        $controllers->get('/{order_id}/', 'controller.prod.order:displayOneOrder')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order')
            ->assert('order_id', '\d+');

        $controllers->post('/{order_id}/send/', 'controller.prod.order:sendOrder')
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireOrdersAdmin();
            })
            ->bind('prod_order_send')
            ->assert('order_id', '\d+');

        $controllers->post('/{order_id}/deny/', 'controller.prod.order:denyOrder')
            ->before(function (Request $request) use ($app) {
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
        $toRemove = [];

        $records = RecordsRequest::fromRequest($app, $request, true, ['cancmd']);
        $hasOneAdmin = [];

        if (!$records->isEmpty()) {
            $order = new OrderEntity();
            $order->setUser($app['authentication']->getUser());
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
                    $hasOneAdmin[$record->get_base_id()] = (Boolean) count($query->on_base_ids([$record->get_base_id()])
                        ->who_have_right(['order_master'])
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

            $noAdmins = $collectionHasOrderAdmins->forAll(function ($key, $hasAdmin) {
                    return false === $hasAdmin;
                });

            if ($noAdmins) {
                $msg = $app->trans('There is no one to validate orders, please contact an administrator');
            }

            $order->setTodo($order->getElements()->count());

            try {
                $app['events-manager']->trigger('__NEW_ORDER__', [
                    'order_id' => $order->getId(),
                    'usr_id'   => $order->getUser()->getId()
                ]);
                $success = true;

                $app['EM']->persist($order);
                $app['EM']->flush();
            } catch (\Exception $e) {

            }

            if ($success) {
                $msg = $app->trans('The records have been properly ordered');
            } else {
                $msg = $app->trans('An error occured');
            }
        } else {
            $msg = $app->trans('There is no record eligible for an order');
        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success' => $success,
                'msg'     => $msg,
            ]);
        }

        return $app->redirectPath('prod_orders', [
            'success' => (int) $success,
            'action'  => 'send'
        ]);
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

        $baseIds = array_keys($app['acl']->get($app['authentication']->getUser())->get_granted_base(['order_master']));

        $ordersList = $app['EM']->getRepository('Phraseanet:Order')->listOrders($baseIds, $offsetStart, $perPage, $sort);
        $total = $app['EM']->getRepository('Phraseanet:Order')->countTotalOrders($baseIds);

        return $app['twig']->render('prod/orders/order_box.html.twig', [
            'page'         => $page,
            'perPage'      => $perPage,
            'total'        => $total,
            'previousPage' => $page < 2 ? false : ($page - 1),
            'nextPage'     => $page >= ceil($total / $perPage) ? false : $page + 1,
            'orders'       => new ArrayCollection($ordersList)
        ]);
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
        $order = $app['EM']->getRepository('Phraseanet:Order')->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        return $app['twig']->render('prod/orders/order_item.html.twig', [
            'order' => $order
        ]);
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
        if (null === $order = $app['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Order')->find($order_id)) {
            throw new NotFoundHttpException('Order not found');
        }
        $basket = $order->getBasket();

        if (null === $basket) {
            $basket = new Basket();
            $basket->setName($app->trans('Commande du %date%', ['%date%' => $order->getCreatedOn()->format('Y-m-d')]));
            $basket->setUser($order->getUser());
            $basket->setPusher($app['authentication']->getUser());

            $app['EM']->persist($basket);
            $app['EM']->flush();
        }

        $n = 0;
        $elements = $request->request->get('elements', []);
        foreach ($order->getElements() as $orderElement) {
            if (in_array($orderElement->getId(), $elements)) {
                $sbas_id = \phrasea::sbasFromBas($app, $orderElement->getBaseId());
                $record = new \record_adapter($app, $sbas_id, $orderElement->getRecordId());

                $basketElement = new BasketElement();
                $basketElement->setRecord($record);
                $basketElement->setBasket($basket);

                $orderElement->setOrderMasterId($app['authentication']->getUser()->getId());
                $orderElement->setDeny(false);
                $orderElement->getOrder()->setBasket($basket);

                $basket->addElement($basketElement);

                $n++;
                $app['acl']->get($basket->getUser())->grant_hd_on($record, $app['authentication']->getUser(), 'order');
            }
        }

        try {
            if ($n > 0) {
                $order->setTodo($order->getTodo() - $n);

                $app['events-manager']->trigger('__ORDER_DELIVER__', [
                    'ssel_id' => $order->getBasket()->getId(),
                    'from'    => $app['authentication']->getUser()->getId(),
                    'to'      => $order->getUser()->getId(),
                    'n'       => $n
                ]);
            }
            $success = true;

            $app['EM']->persist($basket);
            $app['EM']->persist($orderElement);
            $app['EM']->persist($order);
            $app['EM']->flush();
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success'  => $success,
                'msg'      => $success ? $app->trans('Order has been sent') : $app->trans('An error occured while sending, please retry  or contact an admin if problem persists'),
                'order_id' => $order_id
            ]);
        }

        return $app->redirectPath('prod_orders', [
            'success' => (int) $success,
            'action'  => 'send'
        ]);
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
        $order = $app['EM']->getRepository('Phraseanet:Order')->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        $n = 0;
        $elements = $request->request->get('elements', []);
        foreach ($order->getElements() as $orderElement) {
            if (in_array($orderElement->getId(),$elements)) {
                $orderElement->setOrderMasterId($app['authentication']->getUser()->getId());
                $orderElement->setDeny(true);

                $app['EM']->persist($orderElement);
                $n++;
            }
        }

        try {
            if ($n > 0) {
                $order->setTodo($order->getTodo() - $n);

                $app['events-manager']->trigger('__ORDER_NOT_DELIVERED__', [
                    'from' => $app['authentication']->getUser()->getId(),
                    'to'   => $order->getUser()->getId(),
                    'n'    => $n
                ]);
            }
            $success = true;

            $app['EM']->persist($order);
            $app['EM']->flush();
        } catch (\Exception $e) {

        }

        if ('json' === $app['request']->getRequestFormat()) {
            return $app->json([
                'success'  => $success,
                'msg'      => $success ? $app->trans('Order has been denied') : $app->trans('An error occured while denying, please retry  or contact an admin if problem persists'),
                'order_id' => $order_id
            ]);
        }

        return $app->redirectPath('prod_orders', [
            'success' => (int) $success,
            'action'  => 'send'
        ]);
    }
}
