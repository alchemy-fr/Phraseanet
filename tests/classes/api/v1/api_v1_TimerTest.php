<?php


require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Core\PhraseaEvents;
use Silex\Application;
use Symfony\Component\EventDispatcher\Event;

class API_V1_TimerTest extends PhraseanetPHPUnitAbstract
{
    public function testRegister()
    {
        $start = microtime(true);

        $app = new Application();
        $dispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $dispatcher->expects($this->exactly(9))
            ->method('addListener');
        $app['dispatcher'] = $dispatcher;
        $app->register(new API_V1_Timer());

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $app['api.timers']);
        $this->assertGreaterThan($start, $app['api.timers.start']);
    }

    public function testTriggerEvent()
    {
        $app = new Application();
        $app->register(new API_V1_Timer());

        $app['dispatcher']->dispatch(PhraseaEvents::API_RESULT, new Event());

        $timers = $app['api.timers']->toArray();

        $this->assertCount(1, $timers);

        $timer = array_pop($timers);

        $this->assertArrayHasKey('name', $timer);
        $this->assertArrayHasKey('memory', $timer);
        $this->assertArrayHasKey('time', $timer);

        $this->assertEquals(PhraseaEvents::API_RESULT, $timer['name']);
        $this->assertGreaterThan(0, $timer['time']);
        $this->assertGreaterThan(400000, $timer['memory']);
    }
}
