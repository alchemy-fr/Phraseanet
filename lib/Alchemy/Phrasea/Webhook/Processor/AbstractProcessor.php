<?php

namespace Alchemy\Phrasea\Webhook\Processor;

use Alchemy\Phrasea\Application;

abstract class AbstractProcessor
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
