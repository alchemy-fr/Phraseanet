<?php

namespace Alchemy\Phrasea\Webhook\Processor;

class UserProcessorFactory implements ProcessorFactory
{

    /**
     * @return ProcessorInterface|UserProcessor
     */
    public function createProcessor()
    {
        return new UserProcessor();
    }
}
