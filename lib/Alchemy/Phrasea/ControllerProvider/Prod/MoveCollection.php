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
use Alchemy\Phrasea\Controller\Prod\MoveCollectionController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class MoveCollection implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.move-collection'] = $app->share(function (PhraseaApplication $app) {
            return (new MoveCollectionController($app));
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
            $firewall
                ->requireRight(\ACL::CANADDRECORD)
                ->requireRight(\ACL::CANDELETERECORD);
        });

        $controllers->post('/', 'controller.prod.move-collection:displayForm')
            ->bind('prod_move_collection');

        $controllers->post('/apply/', 'controller.prod.move-collection:apply')
            ->bind('prod_move_collection_apply');

        return $controllers;
    }
}
