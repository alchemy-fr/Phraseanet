<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Event\Subscriber\ContentNegotiationSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\CookiesDisablerSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\LogoutSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\MaintenanceSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\PhraseaLocaleSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\RecordEditSubscriber;
use Alchemy\Phrasea\Core\Event\Subscriber\SessionManagerSubscriber;
use Silex\Application;
use Silex\ServiceProviderInterface;

class PhraseaEventServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.logout-subscriber'] = $app->share(function (Application $app) {
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
        $app['phraseanet.content-negotiation-subscriber'] = $app->share(function (Application $app) {
            return new ContentNegotiationSubscriber($app);
        });
        $app['phraseanet.record-edit-subscriber'] = $app->share(function (Application $app) {
            return new RecordEditSubscriber();
        });
    }

    public function boot(Application $app)
    {
    }
}
