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

use Alchemy\Phrasea\Core\Event\RecordEvent\BuildSubDefEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeCollectionEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeMetadataEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeOriginalNameEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\ChangeStatusEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\CreateRecordEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\DeleteRecordEvent;
use Alchemy\Phrasea\Core\Event\RecordEvent\RecordIndexEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::RECORD_CHANGE_COLLECTION => 'onChangeCollection',
            PhraseaEvents::RECORD_CREATE => 'onCreate',
            PhraseaEvents::RECORD_DELETE => 'onDelete',
            PhraseaEvents::RECORD_CHANGE_METADATA => 'onChangeMetadata',
            PhraseaEvents::RECORD_CHANGE_STATUS => 'onChangeStatus',
            PhraseaEvents::RECORD_BUILD_SUB_DEFINITION => 'onBuildSubDef',
            PhraseaEvents::RECORD_CHANGE_ORIGINAL_NAME => 'onChangeOriginalName',
        ];
    }

    public function onChangeCollection(ChangeCollectionEvent $event)
    {
        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_UPDATE_RECORD, new RecordIndexEvent($event->getRecord()));
    }

    public function onCreate(CreateRecordEvent $event)
    {
        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_NEW_RECORD, new RecordIndexEvent($event->getRecord()));
    }

    public function onDelete(DeleteRecordEvent $event)
    {
        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_REMOVE_RECORD, new RecordIndexEvent($event->getRecord()));
    }

    public function onChangeMetadata(ChangeMetadataEvent $event)
    {
        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_UPDATE_RECORD, new RecordIndexEvent($event->getRecord()));
    }

    public function onChangeStatus(ChangeStatusEvent $event)
    {
        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_UPDATE_RECORD, new RecordIndexEvent($event->getRecord()));
    }

    public function onBuildSubDef(BuildSubDefEvent $event)
    {
        if (in_array($event->getSubDefName(), ['thumbnail', 'thumbnailgif', 'preview'])) {
            $dispatcher = $event->getDispatcher();
            $dispatcher->dispatch(PhraseaEvents::INDEX_UPDATE_RECORD, new RecordIndexEvent($event->getRecord()));
        }
    }

    public function onChangeOriginalName(ChangeOriginalNameEvent $event)
    {
        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_UPDATE_RECORD, new RecordIndexEvent($event->getRecord()));
    }
}
