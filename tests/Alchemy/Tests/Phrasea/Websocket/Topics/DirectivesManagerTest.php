<?php

namespace Alchemy\Tests\Phrasea\Websocket\Topics;

use Alchemy\Phrasea\Websocket\Consumer\Consumer;
use Alchemy\Phrasea\Websocket\Topics\Directive;
use Alchemy\Phrasea\Websocket\Topics\DirectivesManager;
use Ratchet\Wamp\Topic;

class DirectivesManagerTest extends \PhraseanetTestCase
{
    public function testHasAccess()
    {
        $manager = new DirectivesManager([
            new Directive('http://topic', false, []),
            new Directive('http://topic2', true, []),
            new Directive('http://topic3', true, ['neutron']),
            new Directive('http://topic4', true, ['bouteille']),
            new Directive('http://topic4', true, ['neutron', 'romain']),
        ]);

        $consumer = new Consumer(42, []);
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic')));
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic2')));
        $this->assertFalse($manager->hasAccess($consumer, new Topic('http://topic3')));
        $this->assertFalse($manager->hasAccess($consumer, new Topic('http://topic4')));

        $consumer = new Consumer(null, []);
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic')));
        $this->assertFalse($manager->hasAccess($consumer, new Topic('http://topic2')));
        $this->assertFalse($manager->hasAccess($consumer, new Topic('http://topic3')));
        $this->assertFalse($manager->hasAccess($consumer, new Topic('http://topic4')));

        $consumer = new Consumer(42, ['neutron']);
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic')));
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic2')));
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic3')));
        $this->assertFalse($manager->hasAccess($consumer, new Topic('http://topic4')));

        $consumer = new Consumer(42, ['neutron', 'bouteille', 'romain']);
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic')));
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic2')));
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic3')));
        $this->assertTrue($manager->hasAccess($consumer, new Topic('http://topic4')));
    }
}
