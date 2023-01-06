<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Order\Controller;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\UserQueryAware;
use Alchemy\Phrasea\Controller\Prod\OrderControllerException;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Order\OrderFiller;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProdOrderController extends BaseOrderController
{
    use EntityManagerAware;
    use UserQueryAware;

    /**
     * Create a new order
     *
     * @param Request     $request
     *
     * @return RedirectResponse|JsonResponse
     */
    public function createOrder(Request $request)
    {
        $records = RecordsRequest::fromRequest($this->app, $request, true, [\ACL::CANCMD]);

        try {
            if ($records->isEmpty()) {
                throw new OrderControllerException($this->app->trans('There is no record eligible for an order'));
            }

            if (null !== $deadLine = $request->request->get('deadline')) {
                $deadLine = new \DateTime($deadLine);
            }

            $orderUsage = $request->request->get('use', '');

            $order = new Order();
            $order->setUser($this->getAuthenticatedUser());
            $order->setDeadline($deadLine);
            $order->setOrderUsage($orderUsage);

            $filler = new OrderFiller($this->app['repo.collection-references'], $this->getEntityManager());

            try {
                $filler->assertAllRecordsHaveOrderMaster($records);
            } catch (\RuntimeException $exception) {
                throw new OrderControllerException($this->app->trans('There is no one to validate orders, please contact an administrator'));
            }

            $filler->fillOrder($order, $records);

            $this->dispatch(PhraseaEvents::ORDER_CREATE, new OrderEvent($order));

            $success = true;
            $msg = $this->app->trans('The records have been properly ordered');
        } catch (OrderControllerException $exception) {
            $success = false;
            $msg = $exception->getMessage();
        } catch (\Exception $exception) {
            $success = false;
            $msg = $this->app->trans('An error occurred');
        }

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success' => $success,
                'msg'     => $msg,
            ]);
        }

        return $this->app->redirectPath('prod_orders', [
            'success' => (int) $success,
            'action'  => 'send',
        ]);
    }

    /**
     * Display list of orders
     *
     * @param Request     $request
     *
     * @return Response
     */
    public function displayOrders(Request $request)
    {
        $page = (int) $request->query->get('page', 1);
        $perPage = (int) $request->query->get('per-page', 10);
        $offsetStart = 0;

        $todo = $request->query->get('todo', Order::STATUS_TODO);
        $start = $request->query->get('start', Order::STATUS_NO_FILTER);
        $limit = $request->query->get('limit', []);

        if ($page > 0) {
            $offsetStart = ($page - 1) * $perPage;
        }

        $sort = $request->query->get('sort');

        $baseIds = array_keys($this->getAclForUser()->get_granted_base([\ACL::ORDER_MASTER]));

        $ordersListTodo = $this->getOrderRepository()->listOrders($baseIds, $offsetStart, $perPage, $sort,
            ['todo' => Order::STATUS_TODO, 'created_on' => $start, 'limit' => $limit]);
        $ordersListProcessed = $this->getOrderRepository()->listOrders($baseIds, $offsetStart, $perPage, $sort,
            ['todo' => Order::STATUS_PROCESSED, 'created_on' => $start, 'limit' => $limit]);
        $totalTodo = $this->getOrderRepository()->countTotalOrders($baseIds, ['todo' => Order::STATUS_TODO, 'created_on' => $start, 'limit' => $limit]);
        $totalProcessed = $this->getOrderRepository()->countTotalOrders($baseIds, ['todo' => Order::STATUS_PROCESSED, 'created_on' => $start, 'limit' => $limit]);

        return $this->render('prod/orders/order_box.html.twig', [
            'page'         => $page,
            'perPage'      => $perPage,
            'totalTodo'        => $totalTodo,
            'totalProcessed' => $totalProcessed,
            'orders_todo'       => new ArrayCollection($ordersListTodo),
            'orders_processed'       => new ArrayCollection($ordersListProcessed),
            'todo' => $todo,
            'start' => $start,
            'date' => $limit ?  $limit['date']: null
        ]);
    }

    /**
     * Display a single order identified by its id
     *
     * @param  integer     $order_id
     * @return Response
     */
    public function displayOneOrder($order_id)
    {
        $order = $this->findOr404($order_id);
        $grantedBaseIds = array_keys($this->getAclForUser()->get_granted_base([\ACL::ORDER_MASTER]));

        return $this->render('prod/orders/order_item.html.twig', [
            'order'             => $order,
            'grantedBaseIds'    => $grantedBaseIds
        ]);
    }

    /**
     * Send an order
     *
     * @param  Request $request
     * @param  integer $order_id
     * @return RedirectResponse|JsonResponse
     */
    public function sendOrder(Request $request, $order_id)
    {
        $elementIds = $request->request->get('elements', []);
        $acceptor = $this->getAuthenticatedUser();

        if (empty($request->request->get('expireOn'))) {
            $expireOn = null;
        } else {
            try {
                $expireOn = new \DateTime($request->request->get('expireOn') . ' 23:59:59');
            } catch (\Exception $e) {
                $expireOn = null;
            }
        }

        $basketElements = $this->doAcceptElements($order_id, $elementIds, $acceptor, $expireOn);

        $success = !empty($basketElements);

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success'  => $success,
                'msg'      => $success
                    ? $this->app->trans('Order has been sent')
                    : $this->app->trans('An error occured while sending, please retry  or contact an admin if problem persists'),
                'order_id' => $order_id,
            ]);
        }

        return $this->app->redirectPath('prod_orders', [
            'success' => (int) $success,
            'action'  => 'send',
        ]);
    }

    /**
     * Deny an order
     *
     * @param  Request                       $request
     * @param  integer                       $order_id
     * @return RedirectResponse|JsonResponse
     */
    public function denyOrder(Request $request, $order_id)
    {
        $elementIds = $request->request->get('elements', []);
        $acceptor = $this->getAuthenticatedUser();

        $elements = $this->doDenyElements($order_id, $elementIds, $acceptor);

        $success = !empty($elements);

        if ('json' === $request->getRequestFormat()) {
            return $this->app->json([
                'success'  => $success,
                'msg'      => $success
                    ? $this->app->trans('Order has been denied')
                    : $this->app->trans('An error occured while denying, please retry  or contact an admin if problem persists'),
                'order_id' => $order_id,
            ]);
        }

        return $this->app->redirectPath('prod_orders', [
            'success' => (int) $success,
            'action'  => 'send',
        ]);
    }

    public function validateOrder(Request $request, $order_id)
    {
        $elementSendIds = $request->request->get('elementsSend', []);
        $elementDenyIds = $request->request->get('elementsDeny', []);
        $acceptor = $this->getAuthenticatedUser();

        if (!empty($elementSendIds)) {
            if (empty($request->request->get('expireOn'))) {
                $expireOn = null;
            } else {
                try {
                    $expireOn = new \DateTime($request->request->get('expireOn') . ' 23:59:59');
                } catch (\Exception $e) {
                    $expireOn = null;
                }
            }

            $basketElements = $this->doAcceptElements($order_id, $elementSendIds, $acceptor, $expireOn);
        }

        if (!empty($elementDenyIds)) {
            $elementsDeny = $this->doDenyElements($order_id, $elementDenyIds, $acceptor);
        }

        $success = !empty($basketElements) || !empty($elementsDeny);

        return $this->app->json([
            'success'  => $success,
            'msg'      => $success
                ? $this->app->trans('Order has been sent')
                : $this->app->trans('An error occured while sending, please retry  or contact an admin if problem persists'),
            'order_id' => $order_id,
        ]);
    }
}
