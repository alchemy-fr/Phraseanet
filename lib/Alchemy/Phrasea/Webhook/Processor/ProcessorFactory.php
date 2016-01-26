<?php

namespace Alchemy\Phrasea\Webhook\Processor;

interface ProcessorFactory
{
    /**
     * @return ProcessorInterface
     */
    public function createProcessor();
}
