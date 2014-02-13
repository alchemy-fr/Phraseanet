<?php

namespace Alchemy\Tests\Phrasea\Websocket\Topics;

use Alchemy\Phrasea\Websocket\Topics\TopicsManager;
use Ratchet\Wamp\Topic;

class TopicsManagerTest extends \PhraseanetTestCase
{
    public function testAttach()
    {
        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $plugin = $this->getMock('Alchemy\Phrasea\Websocket\Topics\Plugin\PluginInterface');
        $plugin->expects($this->once())
            ->method('attach')
            ->with($manager);

        $this->assertSame($manager, $manager->attach($plugin));
    }

    public function testSubscribeWithAccess()
    {
        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->User = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');

        $topic = $this->getMockBuilder('Ratchet\Wamp\Topic')
            ->disableOriginalConstructor()
            ->getMock();

        $topic->expects($this->never())
            ->method('remove');

        $directivesManager->expects($this->once())
            ->method('hasAccess')
            ->will($this->returnValue(true));

        $manager->subscribe($conn, $topic);
    }

    public function testSubscribeWithoutAccess()
    {
        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->User = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');

        $topic = $this->getMockBuilder('Ratchet\Wamp\Topic')
            ->disableOriginalConstructor()
            ->getMock();

        $topic->expects($this->once())
            ->method('remove')
            ->with($conn);

        $directivesManager->expects($this->once())
            ->method('hasAccess')
            ->will($this->returnValue(false));

        $manager->subscribe($conn, $topic);
    }

    public function testUnsubscribe()
    {
        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();

        $directivesManager->expects($this->once())
            ->method('hasAccess')
            ->will($this->returnValue(true));

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->User = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');

        $topic = new Topic('http://topic');
        $topic->add($conn);

        // should be subscribed to be unsubscribed
        $manager->subscribe($conn, $topic);
        $manager->unsubscribe($conn, $topic);

        $this->assertFalse($topic->has($conn));
    }

    public function testOpenConnection()
    {
        $consumer = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');
        $session = $this->getMock('Symfony\Component\HttpFoundation\Session\SessionInterface');

        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();
        $consumerManager->expects($this->once())
            ->method('create')
            ->with($session)
            ->will($this->returnValue($consumer));

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->Session = $session;

        $manager->openConnection($conn);

        $this->assertSame($consumer, $conn->User);
    }

    public function testCloseConnection()
    {
        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();

        $directivesManager->expects($this->once())
            ->method('hasAccess')
            ->will($this->returnValue(true));

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->User = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');

        $topic = new Topic('http://topic');
        $topic->add($conn);

        // should be subscribed to be unsubscribed
        $manager->subscribe($conn, $topic);
        $manager->closeConnection($conn);

        $this->assertFalse($topic->has($conn));
    }

    public function testBroadcast()
    {
        $directivesManager = $this->createDirectivesManagerMock();
        $consumerManager = $this->createConsumerManagerMock();

        $directivesManager->expects($this->once())
            ->method('hasAccess')
            ->will($this->returnValue(true));

        $manager = new TopicsManager($directivesManager, $consumerManager);

        $conn = $this->getMock('Ratchet\ConnectionInterface');
        $conn->User = $this->getMock('Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface');

        $topic = $this->getMockBuilder('Ratchet\Wamp\Topic')
            ->disableOriginalConstructor()
            ->getMock();
        $topic->expects($this->any())
            ->method('getId')
            ->will($this->returnValue('http://topic'));
        $topic->expects($this->once())
            ->method('broadcast')
            ->with('hello world !');

        // should be subscribed to be unsubscribed
        $manager->subscribe($conn, $topic);
        $manager->broadcast('http://topic', 'hello world !');
        $manager->broadcast('http://topic2', 'nothing');
    }

    private function createDirectivesManagerMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Websocket\Topics\DirectivesManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createConsumerManagerMock()
    {
        return $this->getMockBuilder('Alchemy\Phrasea\Websocket\Consumer\ConsumerManager')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
