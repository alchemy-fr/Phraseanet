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

use Alchemy\Phrasea\Websocket\Consumer\ConsumerInterface;

/**
 * Stores consumer required settings for a topic
 */
class Directive
{
    private $topic;
    private $requireAuthentication;
    private $requiredRights;

    public function __construct($topic, $requireAuthentication, array $requiredRights)
    {
        $this->topic = $topic;
        $this->requireAuthentication = (Boolean) $requireAuthentication;
        $this->requiredRights = $requiredRights;
    }

    /**
     * @return string
     */
    public function getTopic()
    {
        return $this->topic;
    }

    /**
     * Returns true if the topic requires an authenticated consumer
     *
     * @return Boolean
     */
    public function requireAuthentication()
    {
        return $this->requireAuthentication;
    }

    /**
     * Returns an array of required rights for the authenticated consumer
     *
     * @return array
     */
    public function getRequiredRights()
    {
        return $this->requiredRights;
    }

    /**
     * Returns true if the consumer satisfies the directive
     *
     * @param ConsumerInterface $consumer
     *
     * @return Boolean
     */
    public function isStatisfiedBy(ConsumerInterface $consumer)
    {
        if ($this->requireAuthentication() && !$consumer->isAuthenticated()) {
            return false;
        }

        return $consumer->hasRights($this->getRequiredRights());
    }
}
