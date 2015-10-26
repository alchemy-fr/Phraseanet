<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Model\Entities\WebhookEvent;

interface ProcessorInterface
{
    public function process(WebhookEvent $event);
}
