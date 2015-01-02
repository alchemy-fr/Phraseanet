<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\RecordEvent\CreateStoryEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\DeleteStoryEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\SearchEngine\Elastic\Indexer\RecordIndexer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StorySubscriber implements EventSubscriberInterface
{
    private $indexer;

    public function __construct(RecordIndexer $indexer)
    {
        $this->indexer = $indexer;
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::STORY_CREATE => 'onCreate',
            PhraseaEvents::STORY_DELETE => 'onDelete',
        ];
    }

    public function onCreate(CreateStoryEvent $event)
    {
        $this->indexer->indexSingleRecord($event->getRecord());
    }

    public function onDelete(DeleteStoryEvent $event)
    {
        $this->indexer->deleteSingleRecord($event->getRecord());
    }
}
