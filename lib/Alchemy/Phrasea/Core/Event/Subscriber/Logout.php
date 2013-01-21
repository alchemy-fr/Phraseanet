<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Logout implements EventSubscriberInterface
{
    public function onLogout(LogoutEvent $event)
    {
        $app = $event->getApplication();
        $app['phraseanet.SE']->clearCache();
    }

    public static function getSubscribedEvents()
    {
        return array(
            PhraseaEvents::LOGOUT => 'onLogout',
        );
    }
}