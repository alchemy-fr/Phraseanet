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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Core\Event\OrderDeliveryEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\BasketElement;
use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\OrderElementRepository;
use Alchemy\Phrasea\Model\Repositories\OrderRepository;
use Alchemy\Phrasea\Order\OrderBasketProvider;
use Alchemy\Phrasea\Order\OrderDelivery;
use Alchemy\Phrasea\Order\OrderValidator;
use Alchemy\Phrasea\Order\PartialOrder;
use Alchemy\Phrasea\Record\RecordReferenceCollection;
use Assert\Assertion;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class BaseOrderController extends Controller
{
    use DispatcherAware;
    use EntityManagerAware;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var OrderElementRepository
     */
    private $orderElementRepository;

    /**
     * @var OrderBasketProvider
     */
    private $orderBasketProvider;

    /**
     * @param Application $app
     * @param OrderRepository $orderRepository
     * @param OrderElementRepository $orderElementRepository
     * @param OrderBasketProvider $orderBasketProvider
     */
    public function __construct(
        Application $app,
        OrderRepository $orderRepository,
        OrderElementRepository $orderElementRepository,
        OrderBasketProvider $orderBasketProvider
    ) {
        parent::__construct($app);

        $this->orderRepository = $orderRepository;
        $this->orderElementRepository = $orderElementRepository;
        $this->orderBasketProvider = $orderBasketProvider;
    }

    /**
     * @return OrderRepository
     */
    protected function getOrderRepository()
    {
        return $this->orderRepository;
    }

    /**
     * @return OrderElementRepository
     */
    protected function getOrderElementRepository()
    {
        return $this->orderElementRepository;
    }

    /**
     * @param int $orderId
     * @return Order
     */
    protected function findOr404($orderId)
    {
        if (null === $order = $this->getOrderRepository()->find($orderId)) {
            throw new NotFoundHttpException('Order not found');
        }

        return $order;
    }

    /**
     * @param int $orderId
     * @param array<int> $elementIds
     * @param User $acceptor
     * @return OrderElement[]
     */
    protected function findRequestedElements($orderId, $elementIds, User $acceptor)
    {
        try {
            Assertion::isArray($elementIds);
        } catch (\Exception $exception) {
            throw new BadRequestHttpException('Improper request', $exception);
        }

        $elements = $this->getOrderElementRepository()->findBy([
            'id' => $elementIds,
            'order' => $orderId,
        ]);

        if (count($elements) !== count($elementIds)) {
            throw new NotFoundHttpException(sprintf('At least one requested element does not exists or does not belong to order "%s"', $orderId));
        }

        if (!$this->getOrderValidator()->isGrantedValidation($acceptor, $elements)) {
            throw new AccessDeniedHttpException('At least one element is in a collection you have no access to.');
        }

        return $elements;
    }

    /**
     * @return OrderValidator
     */
    protected function getOrderValidator()
    {
        return $this->app['validator.order'];
    }

    /**
     * @param int $order_id
     * @param array<int> $elementIds
     * @param User $acceptor
     * @return BasketElement[]
     */
    protected function doAcceptElements($order_id, $elementIds, User $acceptor)
    {
        $elements = $this->findRequestedElements($order_id, $elementIds, $acceptor);
        $order = $this->findOr404($order_id);

        $basket = $this->orderBasketProvider->provideBasketForOrderAndUser($order, $acceptor);

        $partialOrder = new PartialOrder($order, $elements);

        $orderValidator = $this->getOrderValidator();

        $basketElements = $orderValidator->createBasketElements($partialOrder);
        $this->assertRequestedElementsWereNotAlreadyAdded($basket, $basketElements);

        $orderValidator->accept($acceptor, $partialOrder);
        $orderValidator->grantHD($basket->getUser(), $basketElements);

        try {
            $manager = $this->getEntityManager();

            if (!empty($basketElements)) {
                foreach ($basketElements as $element) {
                    $basket->addElement($element);
                    $manager->persist($element);
                }

                $delivery = new OrderDelivery($order, $acceptor, count($basketElements));

                $this->dispatch(PhraseaEvents::ORDER_DELIVER, new OrderDeliveryEvent($delivery));
            }

            $manager->persist($basket);
            $manager->persist($order);
            $manager->flush();
        } catch (\Exception $e) {
            // I don't know why only basket persistence is not checked
        }

        return $basketElements;
    }

    /**
     * @param int $order_id
     * @param array<int> $elementIds
     * @param User $acceptor
     * @return OrderElement[]
     */
    protected function doDenyElements($order_id, $elementIds, User $acceptor)
    {
        $elements = $this->findRequestedElements($order_id, $elementIds, $acceptor);
        $order = $this->findOr404($order_id);

        $this->getOrderValidator()->deny($acceptor, new PartialOrder($order, $elements));

        try {
            if (!empty($elements)) {
                $delivery = new OrderDelivery($order, $acceptor, count($elements));

                $this->dispatch(PhraseaEvents::ORDER_DENY, new OrderDeliveryEvent($delivery));
            }

            $manager = $this->getEntityManager();
            $manager->persist($order);
            $manager->flush();
        } catch (\Exception $e) {
            // Don't know why this is ignored
        }

        return $elements;
    }

    /**
     * @param Basket $basket
     * @param BasketElement[] $elements
     */
    protected function assertRequestedElementsWereNotAlreadyAdded(Basket $basket, $elements)
    {
        if ($basket->getElements()->isEmpty()) {
            return;
        }

        $basketReferences = new RecordReferenceCollection();

        $basket->getElements()->forAll(function ($index, BasketElement $element) use ($basketReferences) {
            $basketReferences->addRecordReference($element->getSbasId(), $element->getRecordId());
        });

        $toAddReferences = new RecordReferenceCollection();

        foreach ($elements as $element) {
            $toAddReferences->addRecordReference($element->getSbasId(), $element->getRecordId());
        }

        foreach ($toAddReferences->getDataboxIds() as $databoxId) {
            $toAddRecordIds = $toAddReferences->getDataboxRecordIds($databoxId);
            $basketRecordIds = $basketReferences->getDataboxRecordIds($databoxId);

            if (array_intersect($toAddRecordIds, $basketRecordIds)) {
                throw new ConflictHttpException('Some records have already been handled');
            }
        }
    }
}
