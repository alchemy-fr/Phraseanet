<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\Subscriber\LogoutSubscriber;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\LogoutEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class LogoutSubscriberTest extends \PhraseanetTestCase
{
    public function testThatSearchEngineCacheIsCleaned()
    {
        $app = new Application(Application::ENV_TEST);
        $app['dispatcher']->addSubscriber(new LogoutSubscriber());

        $app['phraseanet.SE'] = $this->createStub('Alchemy\Phrasea\SearchEngine\SearchEngineInterface');

        // the method is actually called two times because the event is registered two times
        // in this test and in the applicaton constructor
        $app['phraseanet.SE']->expects($this->exactly(2))
            ->method('clearCache');

        $app['dispatcher']->dispatch(new LogoutEvent($app), PhraseaEvents::LOGOUT);
    }
}
