<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\User;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\User\UserNotificationController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Notifications implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.user.notifications'] = $app->share(function (PhraseaApplication $app) {
            return (new UserNotificationController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireNotGuest();
        });

        /** @uses UserNotificationController::getNotifications() */
        $controllers->get('/', 'controller.user.notifications:getNotifications')
//            ->bind('get_notifications')
        ;

        $controllers->patch('/{notification_id}/', 'controller.user.notifications:patchNotification')
            ->assert('notification_id', '\d+')
            ->bind('set_notifications_readed');

        /** @uses UserNotificationController::readAllNotification() */
        $controllers->post('/read-all/', 'controller.user.notifications:readAllNotification')
            ->bind('set_all_notifications_readed');

        /*
        /** @uses  UserNotificationController::listNotifications * /
        $controllers->get('/', 'controller.user.notifications:getNotifications')
            ->bind('get_notifications');

        /** @uses  UserNotificationController::readNotifications() * /
        $controllers->post('/read/', 'controller.user.notifications:readNotifications')
            ->bind('set_notifications_readed');
        */

        return $controllers;
    }
}
