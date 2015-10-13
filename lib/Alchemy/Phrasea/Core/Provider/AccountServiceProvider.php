<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Account\AccountService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class AccountServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        $app['accounts.service'] = $app->share(function () use ($app) {
            return new AccountService(
                $app['authentication'],
                $app['auth.password-encoder'],
                $app['dispatcher'],
                $app['orm.em'],
                $app['model.user-manager'],
                $app['manipulator.user'],
                $app['repo.users']
            );
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // NO-OP
        return;
    }
}
