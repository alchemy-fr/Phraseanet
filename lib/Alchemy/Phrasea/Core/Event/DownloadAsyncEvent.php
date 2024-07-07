<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Symfony\Component\EventDispatcher\Event as SfEvent;

class DownloadAsyncEvent extends SfEvent
{
    private $userId;
    private $tokenValue;
    /** @var  array */
    private $params;


    public function __construct($userId, $tokenValue, array $params)
    {
        $this->userId     = $userId;
        $this->tokenValue = $tokenValue;
        $this->params     = $params;
    }

    public function getTokenValue()
    {
        return $this->tokenValue;
    }

    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }
}
