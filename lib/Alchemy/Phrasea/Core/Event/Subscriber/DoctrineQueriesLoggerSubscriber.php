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

use Alchemy\Phrasea\Application;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class DoctrineQueriesLoggerSubscriber implements EventSubscriberInterface
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['logQueries', -255],
            ],
        ];
    }

    public function logQueries(GetResponseEvent $event)
    {
        if (Application::ENV_DEV !== $this->app->getEnvironment()) {
            return;
        }

        foreach ($this->app['orm.query.logger']->queries as $query ) {
            $this->app['orm.sql-logger']->debug($query['sql'], array(
                'params' => $query['params'],
                'types' => $query['types'],
                'time' => $query['executionMS']
            ));
        }

    }
}
