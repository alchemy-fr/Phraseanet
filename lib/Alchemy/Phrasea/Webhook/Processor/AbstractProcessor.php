<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\WebhookEvent;

abstract class AbstractProcessor
{
    protected $event;
    protected $app;

    public function __construct(WebhookEvent $event, Application $app)
    {
        $this->event = $event;
        $this->app = $app;
    }
}
