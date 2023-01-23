<?php

namespace Alchemy\Phrasea\Webhook;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

interface WebhookPublisherInterface
{
    public function publishWebhookEvent(WebhookEvent $event);
}
