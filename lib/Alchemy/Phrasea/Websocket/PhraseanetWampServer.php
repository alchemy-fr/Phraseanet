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

use Psr\Log\LoggerInterface;
use React\ZMQ\SocketWrapper;
use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\WampServerInterface;

class PhraseanetWampServer implements WampServerInterface
{
    const TOPIC_TASK_MANAGER = 'http://phraseanet.com/topics/admin/task-manager';

    private $pull;
    private $logger;
    private $topics = [];

    public function __construct(SocketWrapper $pull, LoggerInterface $logger)
    {
        $this->pull = $pull;
        $this->logger = $logger;

        $pull->on('message', function ($msg) {
            $data = @json_decode($msg, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error(sprintf('[WS] Received invalid message %s : invalid json', $msg));

                return;
            }

            if (!isset($data['topic'])) {
                $this->logger->error(sprintf('[WS] Received invalid message %s : no topic', $msg));

                return;
            }

            $this->logger->debug(sprintf('[WS] Received message %s', $msg));

            if (isset($this->topics[$data['topic']])) {
                $this->topics[$data['topic']]->broadcast(json_encode($msg));
            }
        });
    }

    public function onPublish(Conn $conn, $topic, $event, array $exclude, array $eligible)
    {
        $this->logger->error(sprintf('Publishing on topic %s', $topic->getId()), array('event' => $event, 'topic' => $topic));
        $topic->broadcast($event);
    }

    public function onCall(Conn $conn, $id, $topic, array $params)
    {
        $this->logger->error(sprintf('Received RPC call on topic %s', $topic->getId()), array('topic' => $topic));
        $conn->callError($id, $topic, 'RPC not supported on this demo');
    }

    public function onSubscribe(Conn $conn, $topic)
    {
        $this->logger->debug(sprintf('Subscription received on topic %s', $topic->getId()), array('topic' => $topic));
        $this->topics[$topic->getId()] = $topic;
    }

    public function onUnSubscribe(Conn $conn, $topic)
    {
        $this->logger->debug(sprintf('Unsubscription received on topic %s', $topic->getId()), array('topic' => $topic));
        $this->cleanupReferences($conn, $topic->getId());
    }

    public function onOpen(Conn $conn)
    {
        if (!$conn->Session->has('usr_id')) {
            $this->logger->error('[WS] Connection request aborted, no usr_id in session.');
            $conn->close();
        }
        $this->logger->error('[WS] Connection request accepted');
    }

    public function onClose(Conn $conn)
    {
        $this->cleanupReferences($conn);
        $this->logger->error('[WS] Connection closed');
    }

    public function onError(Conn $conn, \Exception $e)
    {
        $this->logger->error('[WS] Connection error', ['exception' => $e]);
    }

    private function cleanupReferences(Conn $conn, $topicId = null)
    {
        $storage = $this->topics;
        $ret = array();

        foreach ($storage as $id => $topic) {
            if (null !== $topicId && $id !== $topicId) {
                continue;
            }
            if ($topic->has($conn)) {
                $topic->remove($conn);
            }
            if (count($topic) > 0) {
                $ret[] = $topic;
            }
            $this->logger->debug(sprintf('%d subscribers remaining on topic %s', count($topic), $topic->getId()), array('topic' => $topic));
        }

        $this->topics = $ret;
    }
}
