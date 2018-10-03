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
use Alchemy\Phrasea\Controller\Admin\FeedController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Feeds implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.admin.feeds'] = $app->share(function (PhraseaApplication $app) {
            return new FeedController($app);
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
            $firewall
                ->requireAccessToModule('admin')
                ->requireRight(\ACL::BAS_CHUPUB);
        });

        $controllers->get('/list/', 'controller.admin.feeds:listFeedsAction')
            ->bind('admin_feeds_list');

        $controllers->post('/create/', 'controller.admin.feeds:createAction')
            ->bind('admin_feeds_create');

        $controllers->get('/feed/{id}/', 'controller.admin.feeds:showAction')
            ->bind('admin_feeds_feed')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/update/', 'controller.admin.feeds:updateAction')
            ->bind('admin_feeds_feed_update')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/iconupload/', 'controller.admin.feeds:uploadIconAction')
            ->bind('admin_feeds_feed_icon')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/addpublisher/', 'controller.admin.feeds:addPublisherAction')
            ->bind('admin_feeds_feed_add_publisher')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/removepublisher/', 'controller.admin.feeds:removePublisherAction')
            ->bind('admin_feeds_feed_remove_publisher')
            ->assert('id', '\d+');

        $controllers->post('/feed/{id}/delete/', 'controller.admin.feeds:deleteAction')
            ->bind('admin_feeds_feed_delete')
            ->assert('id', '\d+');

        return $controllers;
    }
}
