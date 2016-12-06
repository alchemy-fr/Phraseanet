<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Admin;

use Alchemy\Phrasea\Controller\Admin\UserController;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Users implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.users'] = $app->share(function () use ($app) {
            return (new UserController($app))
                ->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'))
                ->setUserQueryFactory(new LazyLocator($app, 'phraseanet.user-query'))
            ;
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireAccessToModule('admin')
                ->requireRight(\ACL::CANADMIN);
        });

        $controllers->match('/rights/', 'controller.admin.users:editRightsAction')
            ->method('GET|POST');
        $controllers->post('/rights/reset/', 'controller.admin.users:resetRightsAction')
            ->bind('admin_users_rights_reset');
        $controllers->post('/delete/', 'controller.admin.users:deleteUserAction');
        $controllers->post('/rights/apply/', 'controller.admin.users:applyRightsAction')
            ->bind('admin_users_rights_apply');
        $controllers->post('/rights/quotas/', 'controller.admin.users:editQuotasRightsAction');
        $controllers->post('/rights/quotas/apply/', 'controller.admin.users:applyQuotasAction');
        $controllers->post('/rights/time/', 'controller.admin.users:editTimeLimitAction');
        $controllers->post('/rights/time/sbas/', 'controller.admin.users:editTimeLimitSbasAction');
        $controllers->post('/rights/time/apply/', 'controller.admin.users:applyTimeAction');
        $controllers->post('/rights/masks/', 'controller.admin.users:editMasksAction');
        $controllers->post('/rights/masks/apply/', 'controller.admin.users:applyMasksAction');
        $controllers->match('/search/', 'controller.admin.users:searchAction')
            ->bind('admin_users_search');
        $controllers->post('/search/export/', 'controller.admin.users:searchExportAction')
            ->bind('admin_users_search_export');
        $controllers->post('/apply_template/', 'controller.admin.users:applyTemplateAction')
            ->bind('admin_users_apply_template');
        $controllers->get('/typeahead/search/', 'controller.admin.users:typeAheadSearchAction');
        $controllers->post('/create/', 'controller.admin.users:createAction');
        $controllers->post('/export/csv/', 'controller.admin.users:exportAction')
            ->bind('admin_users_export_csv');
        $controllers->get('/registrations/', 'controller.admin.users:displayRegistrationsAction')
            ->bind('users_display_registrations');
        $controllers->post('/registrations/', 'controller.admin.users:submitRegistrationAction')
            ->bind('users_submit_registrations');
        $controllers->get('/import/file/', 'controller.admin.users:displayImportFileAction')
            ->bind('users_display_import_file');
        $controllers->post('/import/file/', 'controller.admin.users:submitImportFileAction')
            ->bind('users_submit_import_file');
        $controllers->post('/import/', 'controller.admin.users:submitImportAction')
            ->bind('users_submit_import');
        $controllers->get('/import/example/csv/', 'controller.admin.users:importCsvExampleAction')
            ->bind('users_import_csv');
        $controllers->get('/import/example/rtf/', 'controller.admin.users:importRtfExampleAction')
            ->bind('users_import_rtf');

        return $controllers;
    }
}
