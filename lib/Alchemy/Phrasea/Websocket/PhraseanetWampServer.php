<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Websocket;

use Alchemy\Phrasea\Websocket\Topics\TopicsManager;
use Psr\Log\LoggerInterface;
use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\WampServerInterface;

class PhraseanetWampServer implements WampServerInterface
{
    private $logger;
    private $manager;

    public function __construct(TopicsManager $manager, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible)
    {
        $this->logger->error(sprintf('Publishing on topic %s', $topic->getId()), array('event' => $event, 'topic' => $topic));
        $topic->broadcast($event);
    }

    /**
     * {@inheritdoc}
     */
    public function onCall(Conn $conn, $id, $topic, array $params)
    {
        $this->logger->error(sprintf('Received RPC call on topic %s', $topic->getId()), array('topic' => $topic));
        $conn->callError($id, $topic, 'RPC not supported on this demo');
    }

    /**
     * {@inheritdoc}
     */
    public function onSubscribe(Conn $conn, $topic)
    {
        if ($this->manager->subscribe($conn, $topic)) {
            $this->logger->debug(sprintf('Subscription received on topic %s', $topic->getId()), array('topic' => $topic));
        } else {
            $this->logger->error(sprintf('Subscription received on topic %s, user is not allowed', $topic->getId()), array('topic' => $topic));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onUnSubscribe(Conn $conn, $topic)
    {
        $this->logger->debug(sprintf('Unsubscription received on topic %s', $topic->getId()), array('topic' => $topic));
        $this->manager->unsubscribe($conn, $topic);
    }

    /**
     * {@inheritdoc}
     */
    public function onOpen(Conn $conn)
    {
        $this->logger->debug('[WS] Connection request accepted');
        $this->manager->openConnection($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onClose(Conn $conn)
    {
        $this->logger->debug('[WS] Connection closed');
        $this->manager->closeConnection($conn);
    }

    /**
     * {@inheritdoc}
     */
    public function onError(Conn $conn, \Exception $e)
    {
        $this->logger->error('[WS] Connection error', ['exception' => $e]);
    }
}
