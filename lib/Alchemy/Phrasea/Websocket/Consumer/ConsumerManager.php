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

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class ConsumerManager
{
    /**
     * Creates a consumer given a Session
     *
     * @param Session $session
     *
     * @return Consumer
     */
    public function create(SessionInterface $session)
    {
        $usrId = $session->has('usr_id') ? $session->get('usr_id') : null;
        $rights = $session->has('websockets_rights') ? $session->get('websockets_rights') : [];

        return new Consumer($usrId, $rights);;
    }
}
