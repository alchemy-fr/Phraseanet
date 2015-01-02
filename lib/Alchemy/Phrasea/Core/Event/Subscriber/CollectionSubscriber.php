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

use Alchemy\Phrasea\Core\Event\CollectionEvent\ChangeNameEvent;
use Alchemy\Phrasea\Core\Event\CollectionEvent\CollectionIndexEvent;
use Alchemy\Phrasea\Core\Event\DataboxEvent\IndexEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CollectionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::COLLECTION_CHANGE_NAME => 'onChangeName',
        ];
    }

    public function onChangeName(ChangeNameEvent $event)
    {
        $collection = $event->getCollection();

        $dispatcher = $event->getDispatcher();
        $dispatcher->dispatch(PhraseaEvents::INDEX_COLLECTION, new CollectionIndexEvent($collection));
    }
}
