<?php

/*
 * This file is part of phrasea-4.1.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;
use Alchemy\Phrasea\Model\Repositories\WebhookEventRepository;
use Alchemy\Worker\Worker;

class EventProcessorWorker implements Worker
{

    /**
     * @var WebhookEventRepository
     */
    private $eventRepository;

    /**
     * @var WebhookInvoker
     */
    private $invoker;

    /**
     * @param WebhookEventRepository $eventRepository
     * @param WebhookInvoker $invoke
     */
    public function __construct(WebhookEventRepository $eventRepository, WebhookInvoker $invoke)
    {
        $this->eventRepository = $eventRepository;
        $this->invoker = $invoke;
    }

    /**
     * @param array $payload
     * @return void
     */
    public function process(array $payload)
    {
        $eventId = $payload['id'];
        /** @var WebhookEvent $event */
        $event = $this->eventRepository->find($eventId);

        if ($event === null || $event->isProcessed()) {
            return;
        }

        $this->invoker->invoke($event);
    }
}
