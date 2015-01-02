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

use Alchemy\Phrasea\Core\Event\DataboxEvent\DataboxIndexEvent;
use Alchemy\Phrasea\Core\Event\DataboxEvent\DeleteStatusEvent;
use Alchemy\Phrasea\Core\Event\DataboxEvent\UpdateStatusEvent;
use Alchemy\Phrasea\Core\Event\DataboxEvent\UpdateStructureFieldEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DataboxSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::DATABOX_UPDATE_FIELD => 'onUpdateField',
            PhraseaEvents::DATABOX_DELETE_FIELD => 'onDeleteField',
            PhraseaEvents::DATABOX_UPDATE_STATUS => 'onUpdateStatus',
            PhraseaEvents::DATABOX_DELETE_STATUS => 'onDeleteStatus'
        ];
    }

    public function onUpdateField(UpdateStructureFieldEvent $event)
    {
        $databox = $event->getDatabox();
        $field = $event->getField();
        $data = $event->getData();

        if ((bool) $field->get_thumbtitle() !== (bool) $data['thumbtitle']) {
            $dispatcher = $event->getDispatcher();
            $dispatcher->dispatch(PhraseaEvents::INDEX_DATABOX, new DataboxIndexEvent($databox));
        }
    }

    public function onDeleteField(UpdateStructureFieldEvent $event)
    {
        $databox = $event->getDatabox();

        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_DATABOX, new DataboxIndexEvent($databox));
    }

    public function onUpdateStatus(UpdateStatusEvent $event)
    {
        $databox = $event->getDatabox();

        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_DATABOX, new DataboxIndexEvent($databox));
    }

    public function onDeleteStatus(DeleteStatusEvent $event)
    {
        $databox = $event->getDatabox();

        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_DATABOX, new DataboxIndexEvent($databox));
    }
}
