<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Alchemy\Phrasea\Model\Manipulator\ACLManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiLogManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthCodeManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthRefreshTokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\BasketManipulator;
use Alchemy\Phrasea\Model\Manipulator\LazaretManipulator;
use Alchemy\Phrasea\Model\Manipulator\PresetManipulator;
use Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator;
use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventDeliveryManipulator;
use Alchemy\Phrasea\Model\Manipulator\WebhookEventManipulator;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ManipulatorServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['manipulator.task'] = $app->share(function (Application $app) {
            return new TaskManipulator($app['orm.em'], $app['translator'], $app['task-manager.notifier']);
        });

        $app['manipulator.user'] = $app->share(function (Application $app) {
            return new UserManipulator(
                $app['model.user-manager'],
                $app['auth.password-encoder'],
                $app['geonames.connector'],
                $app['repo.users'],
                $app['random.low'],
                $app['dispatcher']
            );
        });

        $app['manipulator.token'] = $app->share(function (Application $app) {
            return new TokenManipulator(
                $app['orm.em'],
                $app['random.medium'],
                $app['repo.tokens'],
                $app['tmp.download.path'],
                $app['conf']
            );
        });

        $app['manipulator.preset'] = $app->share(function (Application $app) {
            return new PresetManipulator($app['orm.em'], $app['repo.presets']);
        });

        $app['manipulator.acl'] = $app->share(function (Application $app) {
            return new ACLManipulator($app['acl'], $app->getApplicationBox());
        });

        $app['model.user-manager'] = $app->share(function (Application $app) {
            return new UserManager($app['orm.em'], $app->getApplicationBox()->get_connection());
        });

        $app['manipulator.registration'] = $app->share(function (Application $app) {
            return new RegistrationManipulator(
                $app,
                $app['orm.em'],
                $app['acl'],
                $app->getApplicationBox(),
                $app['repo.registrations']
            );
        });

        $app['manipulator.api-application'] = $app->share(function (Application $app) {
            return new ApiApplicationManipulator($app['orm.em'], $app['repo.api-applications'], $app['random.medium']);
        });

        $app['manipulator.api-account'] = $app->share(function (Application $app) {
            return new ApiAccountManipulator($app['orm.em']);
        });

        $app['manipulator.api-oauth-code'] = $app->share(function (Application $app) {
            return new ApiOauthCodeManipulator($app['orm.em'], $app['repo.api-oauth-codes'], $app['random.medium']);
        });

        $app['manipulator.api-oauth-token'] = $app->share(function (Application $app) {
            return new ApiOauthTokenManipulator($app['orm.em'], $app['repo.api-oauth-tokens'], $app['random.medium']);
        });

        $app['manipulator.api-oauth-refresh-token'] = $app->share(function (Application $app) {
            return new ApiOauthRefreshTokenManipulator($app['orm.em'], $app['repo.api-oauth-refresh-tokens'], $app['random.medium']);
        });

        $app['manipulator.api-log'] = $app->share(function (Application $app) {
            return new ApiLogManipulator($app['orm.em'], $app['repo.api-logs']);
        });

        $app['manipulator.webhook-event'] = $app->share(function (Application $app) {
            return new WebhookEventManipulator(
                $app['orm.em'],
                $app['repo.webhook-event'],
                $app['webhook.publisher']
            );
        });

        $app['manipulator.webhook-delivery'] = $app->share(function (Application $app) {
            return new WebhookEventDeliveryManipulator($app['orm.em'], $app['repo.webhook-delivery']);
        });

        $app['manipulator.basket'] = $app->share(function (Application $app) {
            return new BasketManipulator($app, $app['repo.baskets'], $app['orm.em']);
        });

        $app['manipulator.lazaret'] = $app->share(function (Application $app) {
            return new LazaretManipulator($app, $app['repo.lazaret-files'], $app['filesystem'], $app['orm.em']);
        });

    }

    public function boot(SilexApplication $app)
    {
        // no-op
    }
}
