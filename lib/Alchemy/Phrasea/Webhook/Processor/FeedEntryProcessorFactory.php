<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;

class FeedEntryProcessorFactory implements ProcessorFactory
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $application)
    {
        $this->app = $application;
    }

    public function createProcessor()
    {
        return new FeedEntryProcessor(
            $this->app,
            $this->app['repo.feed-entries'],
            $this->app['phraseanet.user-query']
        );
    }
}
