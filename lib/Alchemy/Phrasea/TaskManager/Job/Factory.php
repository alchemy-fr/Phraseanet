<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class Factory
{
    private $dispatcher;
    private $logger;
    private $translator;

    public function __construct(EventDispatcherInterface $dispatcher, LoggerInterface $logger, TranslatorInterface $translator)
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->translator = $translator;
    }

    /**
     * @param string $fqn
     * @return JobInterface
     */
    public function create($fqn)
    {
        if (!class_exists($fqn)) {
            $tryFqn = __NAMESPACE__ . '\\' . $fqn . 'Job';
            if (!class_exists($tryFqn)) {
                throw new InvalidArgumentException(sprintf('Job `%s` not found.', $fqn));
            }
            $fqn = $tryFqn;
        }

        if (!in_array('Alchemy\Phrasea\TaskManager\Job\JobInterface', class_implements($fqn))) {
            throw new InvalidArgumentException(sprintf('Class `%s` does not implement JobInterface.', $fqn));
        }

        return new $fqn($this->translator, $this->dispatcher, $this->logger);
    }
}
