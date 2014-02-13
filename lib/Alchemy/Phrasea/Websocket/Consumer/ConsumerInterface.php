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

interface ConsumerInterface
{
    /**
     * Return true if the consumer is authenticated in Phraseanet
     *
     * @return Boolean
     */
    public function isAuthenticated();

    /**
     * Return true if the user has the given rights
     *
     * @param string\array $rights A right or an array of rights
     *
     * @return Boolean
     */
    public function hasRights($rights);
}
