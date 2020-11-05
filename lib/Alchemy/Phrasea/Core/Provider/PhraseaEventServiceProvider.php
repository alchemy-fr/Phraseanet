<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Event\Subscriber\StructureChangeSubscriber;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Core\Event\Subscriber\ContentNegotiationSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\CookiesDisablerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\LogoutSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\MaintenanceSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\RecordEditSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\SessionManagerSubscriber;
use Alchemy\Phrasea\Record\RecordUpdateSubscriber;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PhraseaEventServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.logout-subscriber'] = $app->share(function () {
            return new LogoutSubscriber();
        });
        $app['phraseanet.locale-subscriber'] = $app->share(function (Application $app) {
            return new PhraseaLocaleSubscriber($app);
        });
        $app['phraseanet.maintenance-subscriber'] = $app->share(function (Application $app) {
            return new MaintenanceSubscriber($app);
        });
        $app['phraseanet.cookie-disabler-subscriber'] = $app->share(function (Application $app) {
            return new CookiesDisablerSubscriber($app);
        });
        $app['phraseanet.session-manager-subscriber'] = $app->share(function (Application $app) {
            return new SessionManagerSubscriber($app);
        });
        $app['phraseanet.content-negotiation.priorities'] = [
            'text/html',
            'application/json',
        ];
        $app['phraseanet.content-negotiation.custom_formats'] = [];
        $app['phraseanet.content-negotiation-subscriber'] = $app->share(function (Application $app) {
            return new ContentNegotiationSubscriber(
                $app['negotiator'],
                $app['phraseanet.content-negotiation.priorities'],
                $app['phraseanet.content-negotiation.custom_formats']
            );
        });
        $app['phraseanet.record-edit-subscriber'] = $app->share(function (Application $app) {
            return new RecordEditSubscriber(new LazyLocator($app, 'phraseanet.appbox'));
        });

        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Application $app) {
                $dispatcher->addSubscriber($app['phraseanet.logout-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.locale-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.content-negotiation-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.maintenance-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.cookie-disabler-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.session-manager-subscriber']);
                $dispatcher->addSubscriber($app['phraseanet.record-edit-subscriber']);

                // if phr is not yet installed, don't use non-existing service 'search_engine.structure'
                if($app->offsetExists('search_engine.structure')) {
                    $dispatcher->addSubscriber(new StructureChangeSubscriber($app['search_engine.structure']));
                }

                return $dispatcher;
            })
        );
    }

    public function boot(Application $app)
    {
        // Nothing to do
    }
}
