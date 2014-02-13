<?php

namespace Alchemy\Tests\Phrasea\Websocket\Consumer;

use Alchemy\Phrasea\Websocket\Consumer\Consumer;

class ConsumerTest extends \PhraseanetTestCase
{
    public function testIsAuthenticated()
    {
        $consumer = new Consumer(42, []);
        $this->assertTrue($consumer->isAuthenticated());
        $consumer = new Consumer(null, []);
        $this->assertFalse($consumer->isAuthenticated());
    }

    public function testHasRights()
    {
        $consumer = new Consumer(42, ['neutron']);
        $this->assertTrue($consumer->hasRights('neutron'));
        $consumer = new Consumer(42, ['neutron']);
        $this->assertTrue($consumer->hasRights(['neutron']));
        $consumer = new Consumer(42, ['romainneutron']);
        $this->assertFalse($consumer->hasRights('neutron'));
        $consumer = new Consumer(42, ['romainneutron']);
        $this->assertFalse($consumer->hasRights(['neutron']));
        $consumer = new Consumer(42, ['neutron']);
        $this->assertFalse($consumer->hasRights(['neutron', 'romain']));
        $consumer = new Consumer(42, ['romain', 'neutron', 'bouteille']);
        $this->assertTrue($consumer->hasRights(['neutron', 'romain']));
    }
}
