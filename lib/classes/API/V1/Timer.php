<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Silex\Application;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\KernelEvents;

class API_V1_Timer implements ServiceProviderInterface
{
    private $starting;

    public function __construct()
    {
        $this->starting = microtime(true);
    }

    public function register(Application $app)
    {
        $app['api.timers'] = new ArrayCollection();
        $app['api.timers.start'] = $this->starting;

        $callback = function (Event $event) use ($app) {
            $name = $event->getName();
            $n = 1;
            while (isset($app['api.timers']->{$name})) {
                $n++;
                $name = $event->getName() . '#' . $n;
            }
            $app['api.timers']->add([
                'name' => $name,
                'memory' => memory_get_usage(),
                'time' => microtime(true) - $app['api.timers.start'],
            ]);
        };

        $app['dispatcher']->addListener(KernelEvents::CONTROLLER, $callback, -999999);
        $app['dispatcher']->addListener(KernelEvents::REQUEST, $callback, 999999);
        $app['dispatcher']->addListener(KernelEvents::REQUEST, $callback, -999999);
        $app['dispatcher']->addListener(KernelEvents::RESPONSE, $callback, -999999);
        $app['dispatcher']->addListener(KernelEvents::EXCEPTION, $callback, 999999);
        $app['dispatcher']->addListener(PhraseaEvents::API_OAUTH2_START, $callback);
        $app['dispatcher']->addListener(PhraseaEvents::API_OAUTH2_END, $callback);
        $app['dispatcher']->addListener(PhraseaEvents::API_LOAD_END, $callback);
        $app['dispatcher']->addListener(PhraseaEvents::API_RESULT, $callback);
    }

    public function boot(Application $app)
    {
    }
}
