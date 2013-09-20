<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Factory
{
    private $dispatcher;
    private $logger;

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
    }

    public function create($fqn)
    {
        if (!class_exists($fqn)) {
            throw new InvalidArgumentException(sprintf('Job `%s` not found.', $fqn));
        }

        if (!in_array('Alchemy\Phrasea\TaskManager\Job\JobInterface', class_implements($fqn))) {
            throw new InvalidArgumentException(sprintf('Class `%s` does not implement JobInterface.', $fqn));
        }

        return new $fqn($this->dispatcher, $this->logger);
    }
}
