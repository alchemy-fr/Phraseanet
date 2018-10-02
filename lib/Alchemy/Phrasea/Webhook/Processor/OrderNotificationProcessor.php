<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\Order;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Repositories\OrderRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;

class OrderNotificationProcessor implements ProcessorInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(OrderRepository $orderRepository, UserRepository $userRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->userRepository = $userRepository;
    }

    public function process(WebhookEvent $event)
    {
        if ($event->getName() == WebhookEvent::ORDER_CREATED) {
            return $this->processCreateOrder($event);
        }

        return $this->processDeliveryOrder($event);
    }

    protected function processCreateOrder(WebhookEvent $event)
    {
        $data = $event->getData();

        /** @var User $user */
        $user = $this->userRepository->find($data['user_id']);
        /** @var Order $order */
        $order = $this->orderRepository->find($data['order_id']);

        return $this->getOrderData($event, $user, $order);
    }

    protected function processDeliveryOrder(WebhookEvent $event)
    {
        $data = $event->getData();

        /** @var Order $order */
        $order = $this->orderRepository->find($data['order_id']);
        $user = $order->getUser();

        return $this->getOrderData($event, $user, $order);
    }

    /**
     * @param WebhookEvent $event
     * @param User $user
     * @param Order $order
     * @return array
     */
    protected function getOrderData(WebhookEvent $event, User $user, Order $order)
    {
        return [
            'event' => $event->getName(),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'login' => $user->getLogin()
            ],
            'order' => $order->getId()
        ];
    }
}
