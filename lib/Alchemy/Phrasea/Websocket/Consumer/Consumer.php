<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Websocket\Consumer;

/**
 * Websocket consumer
 */
class Consumer implements ConsumerInterface
{
    private $usrId;
    private $rights;

    public function __construct($usrId, array $rights)
    {
        $this->usrId = $usrId;
        $this->rights = $rights;
    }

    /**
     * {@inheritdoc}
     */
    public function isAuthenticated()
    {
        return $this->usrId !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasRights($rights)
    {
        return count(array_intersect($this->rights, (array) $rights)) === count($rights);
    }
}
