<?php

/*
 * This file is part of phrasea-4.0.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Webhook\Processor;

class UserDeletedProcessorFactory implements ProcessorFactory
{

    /**
     * @return ProcessorInterface
     */
    public function createProcessor()
    {
        return new UserDeletedProcessor();
    }
}
