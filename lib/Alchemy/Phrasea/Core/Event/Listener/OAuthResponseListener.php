<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Core\Event\Listener;

use Alchemy\Phrasea\Authentication\Authenticator;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class OAuthResponseListener
{
    /** @var Authenticator */
    private $authenticator;

    public function __construct(Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
    }

    public function __invoke(FilterResponseEvent $event)
    {
        $this->authenticator->closeAccount();

        $event->getDispatcher()->removeListener($event->getName(), $this);
    }
}
