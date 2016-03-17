<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Application\Helper\UserQueryAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Alchemy\Phrasea\Core\Event\OrderDeliveryEvent;
use Alchemy\Phrasea\Core\Event\OrderEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order as OrderEntity;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Repositories\OrderElementRepository;
use Alchemy\Phrasea\Model\Repositories\OrderRepository;
use Alchemy\Phrasea\Order\OrderFiller;
use Assert\Assertion;
use Doctrine\Common\Collections\ArrayCollection;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends Controller
{
    use DispatcherAware;
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
        $records = RecordsRequest::fromRequest($this->app, $request, true, ['cancmd']);

        try {
            if ($records->isEmpty()) {
                throw new OrderControllerException($this->app->trans('There is no record eligible for an order'));
            }

            if (null !== $deadLine = $request->request->get('deadline')) {
                $deadLine = new \DateTime($deadLine);
            }

            $orderUsage = $request->request->get('use', '');

            $order = new OrderEntity();
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
        $offsetStart = $page - 1;
        $perPage = (int) $request->query->get('per-page', 10);
        $sort = $request->query->get('sort');

        $baseIds = array_keys($this->getAclForUser()->get_granted_base(['order_master']));

        $ordersList = $this->getOrderRepository()->listOrders($baseIds, $offsetStart, $perPage, $sort);
        $total = $this->getOrderRepository()->countTotalOrders($baseIds);

        return $this->render('prod/orders/order_box.html.twig', [
            'page'         => $page,
            'perPage'      => $perPage,
            'total'        => $total,
            'previousPage' => $page < 2 ? false : ($page - 1),
            'nextPage'     => $page >= ceil($total / $perPage) ? false : $page + 1,
            'orders'       => new ArrayCollection($ordersList),
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
        $order = $this->getOrderRepository()->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        return $this->render('prod/orders/order_item.html.twig', [
            'order' => $order,
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

        try {
            Assertion::isArray($elementIds);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Improper request', $exception);
        }

        /** @var OrderElement[] $elements */
        $elements = $this->getOrderElementRepository()->findBy([
            'id' => $elementIds,
            'order' => $order_id,
        ]);

        if (count($elements) !== count($elementIds)) {
            throw new NotFoundHttpException(sprintf('At least one requested element does not exists or does not belong to order "%s"', $order_id));
        }

        /** @var Order $order */
        if (null === $order = $this->getOrderRepository()->find($order_id)) {
            throw new NotFoundHttpException('Order not found');
        }

        $success = false;

        $acceptor = $this->getAuthenticatedUser();

        if ($this->app['validator.order']->isGrantedValidation($acceptor, $elements)) {
            throw new AccessDeniedHttpException('At least one element is in a collection you have no access to.');
        }

        $basket = $this->app['provider.order_basket']->provideBasketForOrderAndUser($order, $acceptor);

        $n = 0;

        foreach ($elements as $element) {
            $sbas_id = \phrasea::sbasFromBas($this->app, $element->getBaseId());
            $record = new \record_adapter($this->app, $sbas_id, $element->getRecordId());

            $basketElement = new BasketElement();
            $basketElement->setRecord($record);
            $basketElement->setBasket($basket);

            $element->setOrderMaster($acceptor);
            $element->setDeny(false);
            $element->getOrder()->setBasket($basket);

            $basket->addElement($basketElement);

            $n++;
            $this->getAclForUser($basket->getUser())->grant_hd_on($record, $acceptor, 'order');
        }

        try {
            if ($n > 0) {
                $order->setTodo($order->getTodo() - $n);
                $this->dispatch(PhraseaEvents::ORDER_DELIVER, new OrderDeliveryEvent($order, $acceptor, $n));
            }
            $success = true;

            $manager = $this->getEntityManager();
            $manager->persist($basket);
            $manager->persist($order);
            $manager->flush();
        } catch (\Exception $e) {

        }

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
        $success = false;
        /** @var Order $order */
        $order = $this->getOrderRepository()->find($order_id);
        if (null === $order) {
            throw new NotFoundHttpException('Order not found');
        }

        $n = 0;
        $elements = $request->request->get('elements', []);
        $manager = $this->getEntityManager();
        foreach ($order->getElements() as $orderElement) {
            if (in_array($orderElement->getId(),$elements)) {
                $orderElement->setOrderMaster($this->getAuthenticatedUser());
                $orderElement->setDeny(true);

                $manager->persist($orderElement);
                $n++;
            }
        }

        try {
            if ($n > 0) {
                $order->setTodo($order->getTodo() - $n);
                $this->dispatch(PhraseaEvents::ORDER_DENY, new OrderDeliveryEvent($order, $this->getAuthenticatedUser(), $n));
            }
            $success = true;

            $manager->persist($order);
            $manager->flush();
        } catch (\Exception $e) {

        }

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

    /**
     * @return OrderRepository
     */
    private function getOrderRepository()
    {
        return $this->app['repo.orders'];
    }

    /**
     * @return OrderElementRepository
     */
    private function getOrderElementRepository()
    {
        return $this->app['repo.order-elements'];
    }
}
