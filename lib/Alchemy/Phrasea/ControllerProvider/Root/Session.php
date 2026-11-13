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
use Alchemy\Phrasea\Controller\Root\SessionController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\LazyLocator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\ControllerProviderInterface;
use Silex\Application;

class Session implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Container $app)
    {
        $app['controller.session'] = function (PhraseaApplication $app) {
            return (new SessionController($app))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
            ;
        };
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);

        /** @uses SessionController::updateSession() */
        $controllers->post('/update/', 'controller.session:updateSession')
            ->bind('update_session');

        /** @uses SessionController::deleteSession() */
        // used in admin/connected_users to kill a session
        $controller = $controllers->post('/delete/{id}', 'controller.session:deleteSession')
            ->bind('delete_session');

        $this->getFirewall($app)->addMandatoryAuthentication($controller);

        return $controllers;
    }
}
