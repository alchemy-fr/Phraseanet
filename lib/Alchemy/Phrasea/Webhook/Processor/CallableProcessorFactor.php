<?php

namespace Alchemy\Phrasea\Webhook\Processor;

class CallableProcessorFactory implements ProcessorFactory
{

    private $factoryMethod;

    public function __construct($callable)
    {
        $this->factoryMethod = $callable;
    }

    public function createProcessor()
    {
        $factoryMethod = $this->factoryMethod;

        return $factoryMethod();
    }
}
