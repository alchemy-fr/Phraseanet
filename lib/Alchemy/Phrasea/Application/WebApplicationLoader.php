<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Application;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Subscriber\BridgeExceptionSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\FirewallSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\JsonRequestSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaExceptionHandlerSubscriber;
use Alchemy\Phrasea\Core\Middleware\SetupMiddlewareProvider;

class WebApplicationLoader extends BaseApplicationLoader
{
    protected function doPrePluginServiceRegistration(Application $app)
    {
        $app->register(new SetupMiddlewareProvider());
    }

    protected function createExceptionHandler(Application $app)
    {
        return new PhraseaExceptionHandlerSubscriber($app['phraseanet.exception_handler']);
    }

    protected function bindRoutes(Application $app)
    {
        $app->before($app['setup.validate-config'], Application::EARLY_EVENT);
        $app->bindRoutes();
    }

    protected function getDispatcherSubscribersFor(Application $app)
    {
        $subscribers = [
            new BridgeExceptionSubscriber($app),
            new FirewallSubscriber(),
            new JsonRequestSubscriber(),
        ];

        return array_merge(parent::getDispatcherSubscribersFor($app), $subscribers);
    }
}
