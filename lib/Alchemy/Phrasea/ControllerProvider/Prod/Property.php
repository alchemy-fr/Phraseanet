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
use Alchemy\Phrasea\Controller\Prod\PropertyController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Property implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.property'] = $app->share(function (PhraseaApplication $app) {
            return (new PropertyController($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
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

        $controllers->get('/', 'controller.prod.property:displayStatusProperty')
            ->bind('display_status_property');

        $controllers->get('/type/', 'controller.prod.property:displayTypeProperty')
            ->bind('display_type_property');

        $controllers->post('/status/', 'controller.prod.property:changeStatus')
            ->bind('change_status');

        $controllers->post('/type/', 'controller.prod.property:changeType')
            ->bind('change_type');

        return $controllers;
    }
}
