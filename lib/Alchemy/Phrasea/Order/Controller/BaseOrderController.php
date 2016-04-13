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
use Alchemy\Phrasea\Order\OrderValidator;
use Alchemy\Phrasea\Order\PartialOrder;
use Alchemy\Phrasea\Record\RecordReference;
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
     * @return OrderRepository
     */
    protected function getOrderRepository()
    {
        return $this->app['repo.orders'];
    }

    /**
     * @return OrderElementRepository
     */
    protected function getOrderElementRepository()
    {
        return $this->app['repo.order-elements'];
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

        $basket = $this->app['provider.order_basket']->provideBasketForOrderAndUser($order, $acceptor);

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

                $this->dispatch(PhraseaEvents::ORDER_DELIVER, new OrderDeliveryEvent($order, $acceptor, count($basketElements)));
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
                $this->dispatch(PhraseaEvents::ORDER_DENY, new OrderDeliveryEvent($order, $acceptor, count($elements)));
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

        $references = new RecordReferenceCollection();

        $basket->getElements()->forAll(function (BasketElement $element) use ($references) {
            $references->addRecordReference($element->getSbasId(), $element->getRecordId());
        });

        foreach ($elements as $element) {
            $references->addRecordReference($element->getSbasId(), $element->getRecordId());
        }

        $groups = $references->groupPerDataboxId();

        foreach ($basket->getElements() as $element) {
            if (isset($groups[$element->getSbasId()][$element->getRecordId()])) {
                throw new ConflictHttpException('Some records have already been handled');
            }
        }
    }
}
