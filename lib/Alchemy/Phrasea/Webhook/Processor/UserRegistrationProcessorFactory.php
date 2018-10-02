<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;

class UserRegistrationProcessorFactory implements ProcessorFactory
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    /**
     * @return UserRegistrationProcessor
     */
    public function createProcessor()
    {
        return new UserRegistrationProcessor($this->app['repo.users']);
    }
}
