<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Application;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class LogoutEvent extends SfEvent
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function getApplication()
    {
        return $this->app;
    }
}
