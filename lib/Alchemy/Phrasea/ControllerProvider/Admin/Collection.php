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
use Alchemy\Phrasea\Controller\Admin\CollectionController;
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Collection implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.collection'] = $app->share(function (PhraseaApplication $app) {
            return (new CollectionController($app, $app->getApplicationBox()->getCollectionService()))
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

        $controllers->before(function (Request $request) use ($firewall) {
            $firewall
                ->requireAccessToModule('admin')
                ->requireRightOnBase($request->attributes->get('bas_id'), \ACL::CANADMIN);
        });

        $controllers->get('/{bas_id}/', 'controller.admin.collection:getCollection')
            ->assert('bas_id', '\d+')
            ->bind('admin_display_collection');

        $controllers->get('/{bas_id}/suggested-values/', 'controller.admin.collection:getSuggestedValues')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_display_suggested_values');

        $controllers->post('/{bas_id}/suggested-values/', 'controller.admin.collection:submitSuggestedValues')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_suggested_values');

        $controllers->post('/{bas_id}/delete/', 'controller.admin.collection:delete')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete');

        $controllers->post('/{bas_id}/enable/', 'controller.admin.collection:enable')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_enable');

        $controllers->post('/{bas_id}/disabled/', 'controller.admin.collection:disabled')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_disable');

        $controllers->post('/{bas_id}/order/admins/', 'controller.admin.collection:setOrderAdmins')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_order_admins');

        $controllers->post('/{bas_id}/publication/display/', 'controller.admin.collection:setPublicationDisplay')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_publication');

        $controllers->post('/{bas_id}/rename/', 'controller.admin.collection:rename')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_rename');

        $controllers->post('/{bas_id}/labels/', 'controller.admin.collection:labels')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_labels');

        $controllers->post('/{bas_id}/empty/', 'controller.admin.collection:emptyCollection')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_empty');

        $controllers->post('/{bas_id}/unmount/', 'controller.admin.collection:unmount')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_unmount');

        $controllers->post('/{bas_id}/picture/mini-logo/', 'controller.admin.collection:setMiniLogo')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_logo');

        $controllers->post('/{bas_id}/picture/mini-logo/delete/', 'controller.admin.collection:deleteLogo')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_logo');

        $controllers->post('/{bas_id}/picture/watermark/', 'controller.admin.collection:setWatermark')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_watermark');

        $controllers->post('/{bas_id}/picture/watermark/delete/', 'controller.admin.collection:deleteWatermark')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_watermark');

        $controllers->post('/{bas_id}/picture/stamp-logo/', 'controller.admin.collection:setStamp')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_submit_stamp');

        $controllers->post('/{bas_id}/picture/stamp-logo/delete/', 'controller.admin.collection:deleteStamp')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_delete_stamp');
        $controllers->get('/{bas_id}/informations/details/', 'controller.admin.collection:getDetails')
            ->assert('bas_id', '\d+')
            ->bind('admin_collection_display_document_details');

        return $controllers;
    }
}
