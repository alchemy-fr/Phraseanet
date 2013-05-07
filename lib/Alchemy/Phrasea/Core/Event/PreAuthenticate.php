<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Authentication\Context;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class PreAuthenticate extends SfEvent
{
    private $request;
    private $context;

    public function __construct(Request $request, Context $context)
    {
        $this->request = $request;
        $this->context = $context;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getContext()
    {
        return $this->context;
    }
}
