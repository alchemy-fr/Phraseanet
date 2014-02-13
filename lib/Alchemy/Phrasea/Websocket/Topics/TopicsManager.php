<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Websocket\Topics;

use Alchemy\Phrasea\Websocket\Consumer\Consumer;
use Alchemy\Phrasea\Websocket\Consumer\ConsumerManager;
use Alchemy\Phrasea\Websocket\Topics\Plugin\PluginInterface;
use Ratchet\ConnectionInterface as Conn;
use Ratchet\Wamp\Topic;

class TopicsManager
{
    const TOPIC_TASK_MANAGER = 'http://phraseanet.com/topics/admin/task-manager';

    private $topics = [];
    private $directives;
    private $consumerManager;

    public function __construct(DirectivesManager $directives, ConsumerManager $consumerManagaer)
    {
        $this->directives = $directives;
        $this->consumerManager = $consumerManagaer;
    }

    /**
     * Attaches a plugin to the TopicsManager
     *
     * @param PluginInterface $plugin
     *
     * @return TopicsManager
     */
    public function attach(PluginInterface $plugin)
    {
        $plugin->attach($this);

        return $this;
    }

    /**
     * Checks if the consumer related to the connection has access to the topic,
     * removes the connection from topic if the consumer is not granted.
     *
     * @param Conn  $conn
     * @param Topic $topic
     *
     * @return Boolean Return true if the consumer is granted, false otherwise
     */
    public function subscribe(Conn $conn, Topic $topic)
    {
        if (!$this->directives->hasAccess($conn->User, $topic)) {
            $topic->remove($conn);

            return false;
        }

        $this->topics[$topic->getId()] = $topic;

        return true;
    }

    /**
     * Triggered on unsubscription.
     *
     * Removes internal references to the topic if no more consumers are listening.
     *
     * @param Conn  $conn
     * @param Topic $topic
     *
     * @return TopicsManager
     */
    public function unsubscribe(Conn $conn, Topic $topic)
    {
        $this->cleanupReferences($conn, $topic);

        return $this;
    }

    /**
     * Triggered on connection, populates the connection with a consumer.
     *
     * @param Conn $conn
     *
     * @return TopicsManager
     */
    public function openConnection(Conn $conn)
    {
        try {
            $conn->User = $this->consumerManager->create($conn->Session);
        } catch (\RuntimeException $e) {
            $conn->close();
        }

        return $this;
    }

    /**
     * Triggered on deconnexion.
     *
     * Removes internal references to topics if no more consumers are listening.
     *
     * @param Conn $conn
     *
     * @return TopicsManager
     */
    public function closeConnection(Conn $conn)
    {
        $this->cleanupReferences($conn);

        return $this;
    }

    /**
     * Brodcasts a message to a topic, if it exists
     *
     * @param $topicId string
     * @param $message string
     *
     * @return TopicsManager
     */
    public function broadcast($topicId, $message)
    {
        if (isset($this->topics[$topicId])) {
            $this->topics[$topicId]->broadcast($message);
        }

        return $this;
    }

    /**
     * Removes internal references to topics if they do not contains any reference to an active connection.
     *
     * @param Conn       $conn
     * @param null|Topic $topic Restrict to this topic, if provided
     */
    private function cleanupReferences(Conn $conn, Topic $topic = null)
    {
        $storage = $this->topics;
        $updated = array();

        foreach ($storage as $id => $storedTopic) {
            if (null !== $topic && $id !== $topic->getId()) {
                continue;
            }
            if ($storedTopic->has($conn)) {
                $storedTopic->remove($conn);
            }
            if (count($storedTopic) > 0) {
                $updated[] = $storedTopic;
            }
        }

        $this->topics = $updated;
    }
}
