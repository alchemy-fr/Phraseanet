<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvent;
use Alchemy\Phrasea\Core\Event\Record\Structure\RecordStructureEvents;
use Alchemy\Phrasea\SearchEngine\SearchEngineStructure;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StructureChangeSubscriber implements EventSubscriberInterface
{
    private $searchEngineStructure;

    public function __construct(SearchEngineStructure $se1)
    {
        $this->searchEngineStructure = $se1;
    }

    public static function getSubscribedEvents()
    {
        return [
            RecordStructureEvents::FIELD_UPDATED => 'onStructureChange',
            RecordStructureEvents::FIELD_DELETED => 'onStructureChange',
            RecordStructureEvents::STATUS_BIT_UPDATED => 'onStructureChange',
            RecordStructureEvents::STATUS_BIT_DELETED => 'onStructureChange',
        ];
    }

    /**
     * clears the cached translated versions of phr fields;sb to es fields;sb (createFromLegacy...)
     * this was cached per db
     *
     * @param RecordStructureEvent $event
     */
    public function onStructureChange(RecordStructureEvent $event)
    {
        $this->searchEngineStructure->deleteFromCache($event->getDatabox());
    }
}
