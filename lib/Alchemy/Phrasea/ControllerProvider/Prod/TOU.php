<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\TOUController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class TOU implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.tou'] = $app->share(function (PhraseaApplication $app) {
            return (new TOUController($app));
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

        $controller = $controllers->post('/deny/{sbas_id}/', 'controller.prod.tou:denyTermsOfUse')
            ->bind('deny_tou');
        $firewall->addMandatoryAuthentication($controller);

        $controllers->get('/', 'controller.prod.tou:displayTermsOfUse')
            ->bind('get_tou');

        return $controllers;
    }
}
