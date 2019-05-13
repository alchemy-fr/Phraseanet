<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Root;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Root\AccountController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Account implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['account.controller'] = $app->share(function (PhraseaApplication $app) {
            return (new AccountController($app))
                ->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireNotGuest();
        });

        // Displays current logged in user account
        $controllers->get('/', 'account.controller:displayAccount')
            ->bind('account');

        // allow to delete phraseanet account
        $controllers->get('/delete/process', 'account.controller:processDeleteAccount')
            ->bind('account_process_delete');

        $controllers->get('/delete/confirm', 'account.controller:confirmDeleteAccount')
            ->bind('account_confirm_delete');


        // Updates current logged in user account
        $controllers->post('/', 'account.controller:updateAccount')
            ->bind('submit_update_account');

        // Displays email update form
        $controllers->get('/reset-email/', 'account.controller:displayResetEmailForm')
            ->bind('account_reset_email');

        // Submits a new email for the current logged in account
        $controllers->post('/reset-email/', 'account.controller:resetEmail')
            ->bind('reset_email');

        // Displays current logged in user access and form
        $controllers->get('/access/', 'account.controller:accountAccess')
            ->bind('account_access');

        // Displays and update current logged-in user password
        $controllers->match('/reset-password/', 'account.controller:resetPassword')
            ->bind('reset_password');

        // Displays current logged in user open sessions
        $controllers->get('/security/sessions/', 'account.controller:accountSessionsAccess')
            ->bind('account_sessions');

        // Displays all applications that can access user informations
        $controllers->get('/security/applications/', 'account.controller:accountAuthorizedApps')
            ->bind('account_auth_apps');

        // Displays a an authorized app grant
        $controllers->get('/security/application/{application}/grant/', 'account.controller:grantAccess')
            ->before($app['middleware.api-application.converter'])
            ->assert('application_id', '\d+')
            ->bind('grant_app_access');

        return $controllers;
    }
}
