<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\InstallFinishEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Silex\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Process\Process;

class PhraseaInstallSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            PhraseaEvents::INSTALL_FINISH => 'onInstallFinished'
        ];
    }

    public function onInstallFinished(InstallFinishEvent $event)
    {
        $this->generateProxies();
    }

    private function generateProxies()
    {
        $process = new Process('php ' . $this->app['root.path']. '/bin/developer orm:generate:proxies');
        $process->setTimeout(300);
        $process->run();
    }
}
