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

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Admin\DataboxController;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Security\Firewall;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Databox implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.databox'] = $app->share(function (PhraseaApplication $app) {
            return (new DataboxController($app))
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

        $controllers
            ->before(function (Request $request) use ($app) {
                $app['firewall']->requireAccessToModule('admin')
                    ->requireAccessToSbas($request->attributes->get('databox_id'));
            })
            ->assert('databox_id', '\d+')
        ;

        $controllers->get('/{databox_id}/', 'controller.admin.databox:getDatabase')
            ->bind('admin_database');

        $controllers->post('/{databox_id}/delete/', 'controller.admin.databox:deleteBase')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_delete');

        $controllers->post('/{databox_id}/unmount/', 'controller.admin.databox:unmountDatabase')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_unmount');

        $controllers->post('/{databox_id}/empty/', 'controller.admin.databox:emptyDatabase')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_empty');

        $controllers->get('/{databox_id}/collections/order/', 'controller.admin.databox:getReorder')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_display_collections_order');

        $controllers->post('/{databox_id}/collections/order/', 'controller.admin.databox:setReorder')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_submit_collections_order');

        $controllers->post('/{databox_id}/collection/', 'controller.admin.databox:createCollection')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_submit_collection');

        $controllers->get('/{databox_id}/cgus/', 'controller.admin.databox:getDatabaseCGU')
            ->before([$this, 'requireChangeSbasStructureRight'])
            ->bind('admin_database_display_cgus');

        $controllers->post('/{databox_id}/labels/', 'controller.admin.databox:setLabels')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_databox_labels');

        $controllers->post('/{databox_id}/cgus/', 'controller.admin.databox:updateDatabaseCGU')
            ->before([$this, 'requireChangeSbasStructureRight'])
            ->bind('admin_database_submit_cgus');

        $controllers->get('/{databox_id}/informations/documents/', 'controller.admin.databox:progressBarInfos')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_display_document_information');

        $controllers->get('/{databox_id}/informations/details/', 'controller.admin.databox:getDetails')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_display_document_details');

        $controllers->post('/{databox_id}/collection/{collection_id}/mount/', 'controller.admin.databox:mountCollection')
            ->assert('collection_id', '\d+')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_mount_collection');

        $controllers->get('/{databox_id}/collection/', 'controller.admin.databox:getNewCollection')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_display_new_collection_form');

        $controllers->post('/{databox_id}/logo/', 'controller.admin.databox:sendLogoPdf')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_submit_logo');

        $controllers->post('/{databox_id}/logo/delete/', 'controller.admin.databox:deleteLogoPdf')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_delete_logo');

        $controllers->post('/{databox_id}/clear-logs/', 'controller.admin.databox:clearLogs')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_clear_logs');

        $controllers->post('/{databox_id}/reindex/', 'controller.admin.databox:reindex')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_reindex');

        $controllers->post('/{databox_id}/indexable/', 'controller.admin.databox:setIndexable')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_set_indexable');

        $controllers->post('/{databox_id}/view-name/', 'controller.admin.databox:changeViewName')
            ->before([$this, 'requireManageRightOnSbas'])
            ->bind('admin_database_rename');

        return $controllers;
    }

    public function requireManageRightOnSbas(Request $request, Application $app)
    {
        $this->getFirewall($app)->requireRightOnSbas($request->attributes->get('databox_id'), \ACL::BAS_MANAGE);
    }

    public function requireChangeSbasStructureRight(Request $request, Application $app)
    {
        $this->getFirewall($app)->requireRightOnSbas($request->attributes->get('databox_id'), \ACL::BAS_MODIFY_STRUCT);
    }
}
