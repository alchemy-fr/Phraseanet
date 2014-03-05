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

use Alchemy\Phrasea\Model\Manipulator\ACLManipulator;
use Alchemy\Phrasea\Model\Manipulator\PresetManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiAccountManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiApplicationManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthCodeManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthRefreshTokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\ApiOauthTokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator;
use Alchemy\Phrasea\Model\Manipulator\TaskManipulator;
use Alchemy\Phrasea\Model\Manipulator\TokenManipulator;
use Alchemy\Phrasea\Model\Manipulator\UserManipulator;
use Alchemy\Phrasea\Model\Manager\UserManager;
use Silex\Application as SilexApplication;
use Silex\ServiceProviderInterface;

class ManipulatorServiceProvider implements ServiceProviderInterface
{
    public function register(SilexApplication $app)
    {
        $app['manipulator.task'] = $app->share(function (SilexApplication $app) {
            return new TaskManipulator($app['EM'], $app['task-manager.notifier'], $app['translator'], $app['repo.tasks']);
        });

        $app['manipulator.user'] = $app->share(function ($app) {
            return new UserManipulator($app['model.user-manager'], $app['auth.password-encoder'], $app['geonames.connector'], $app['repo.users'], $app['random.low']);
        });

        $app['manipulator.token'] = $app->share(function ($app) {
            return new TokenManipulator($app['EM'], $app['random.medium'], $app['repo.tokens']);
        });

        $app['manipulator.preset'] = $app->share(function ($app) {
            return new PresetManipulator($app['EM'], $app['repo.presets']);
        });

        $app['manipulator.acl'] = $app->share(function ($app) {
            return new ACLManipulator($app['acl'], $app['phraseanet.appbox']);
        });

        $app['model.user-manager'] = $app->share(function ($app) {
            return new UserManager($app['EM'], $app['phraseanet.appbox']->get_connection());
        });

        $app['manipulator.registration'] = $app->share(function ($app) {
            return new RegistrationManipulator($app, $app['EM'], $app['acl'], $app['phraseanet.appbox'], $app['repo.registrations']);
        });

        $app['manipulator.api-application'] = $app->share(function ($app) {
            return new ApiApplicationManipulator($app['EM'], $app['repo.api-applications'], $app['random.medium']);
        });

        $app['manipulator.api-account'] = $app->share(function ($app) {
            return new ApiAccountManipulator($app['EM'], $app['repo.api-accounts']);
        });

        $app['manipulator.api-oauth-code'] = $app->share(function ($app) {
            return new ApiOauthCodeManipulator($app['EM'], $app['repo.api-oauth-codes'], $app['random.medium']);
        });

        $app['manipulator.api-oauth-token'] = $app->share(function ($app) {
            return new ApiOauthTokenManipulator($app['EM'], $app['repo.api-oauth-tokens'], $app['random.medium']);
        });

        $app['manipulator.api-oauth-refresh-token'] = $app->share(function ($app) {
            return new ApiOauthRefreshTokenManipulator($app['EM'], $app['repo.api-oauth-refresh-tokens'], $app['random.medium']);
        });
    }

    public function boot(SilexApplication $app)
    {
    }
}
