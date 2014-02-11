<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Event;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Removes a task from ObjectManager once the JobEvents::FINISHED is triggered.
 */
class FinishedJobRemoverSubscriber implements EventSubscriberInterface
{
    private $om;

    public function __construct(ObjectManager $om)
    {
        $this->om = $om;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            JobEvents::FINISHED => 'onJobFinish',
        ];
    }

    public function onJobFinish(JobFinishedEvent $event)
    {
        $this->om->remove($event->getTask());
        $this->om->flush();
    }
}
