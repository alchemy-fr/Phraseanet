<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\LogoutSubscriber;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;

class LogoutSubscriberTest extends \PhraseanetTestCase
{
    public function testThatSearchEngineCacheIsCleaned()
    {
        $app = new Application();
        $app['dispatcher']->addSubscriber(new LogoutSubscriber());

        $app['phraseanet.SE'] = $this->getMock('Alchemy\Phrasea\SearchEngine\SearchEngineInterface');

        // the method is actually called two times because the event is registered two times
        // in this test and in the applicaton constructor
        $app['phraseanet.SE']->expects($this->exactly(2))
            ->method('clearCache');

        $app['dispatcher']->dispatch(PhraseaEvents::LOGOUT, new LogoutEvent($app));
    }
}
