<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;

class OrderNotificationProcessorFactory implements ProcessorFactory
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @return ProcessorInterface
     */
    public function createProcessor()
    {
        return new OrderNotificationProcessor(
            $this->application['repo.orders'],
            $this->application['repo.users']
        );
    }
}
