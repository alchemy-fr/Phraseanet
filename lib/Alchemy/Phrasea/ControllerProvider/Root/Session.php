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
use Alchemy\Phrasea\Controller\Root\SessionController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Session implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.session'] = $app->share(function (PhraseaApplication $app) {
            return (new SessionController($app))
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
        $controllers = $this->createCollection($app);

        $controllers->post('/update/', 'controller.session:updateSession')
            ->bind('update_session');

        $controllers->post('/notifications/', 'controller.session:getNotifications')
            ->bind('list_notifications');

        $controller = $controllers->post('/delete/{id}', 'controller.session:deleteSession')
            ->bind('delete_session');

        $this->getFirewall($app)->addMandatoryAuthentication($controller);

        return $controllers;
    }
}
