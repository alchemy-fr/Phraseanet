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
use Ratchet\Wamp\Topic;

class DirectivesManager
{
    private $directives;

    public function __construct(array $directives)
    {
        array_walk($directives, function ($directive) {
            if (!$directive instanceof Directive) {
                throw new \InvalidArgumentException('Websocket configuration only accepts configuration directives.');
            }
        });
        $this->directives = $directives;
    }

    /**
     * Returns true if the consumer has access to the given topic
     *
     * @param ConsumerInterface $consumer
     * @param Topic             $topic
     *
     * @return Boolean
     */
    public function hasAccess(ConsumerInterface $consumer, Topic $topic)
    {
        foreach ($this->getDirectives($topic) as $directive) {
            if (!$directive->isStatisfiedBy($consumer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Topic $topic
     *
     * @return Directive[]
     */
    private function getDirectives(Topic $topic)
    {
        return array_filter($this->directives, function (Directive $directive) use ($topic) {
            return $directive->getTopic() === $topic->getId();
        });
    }
}
