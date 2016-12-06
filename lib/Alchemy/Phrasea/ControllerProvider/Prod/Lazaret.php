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
use Alchemy\Phrasea\Controller\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\LazaretController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Lazaret implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.lazaret'] = $app->share(function (PhraseaApplication $app) {
            return (new LazaretController($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDelivererLocator(new LazyLocator($app, 'phraseanet.file-serve'))
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                ->setSubDefinitionSubstituerLocator(new LazyLocator($app, 'subdef.substituer'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    /**
     * Connect the ControllerCollection to the Silex Application
     *
     * @param  Application                 $app A silex application
     * @return \Silex\ControllerCollection
     */
    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);
        $firewall = $this->getFirewall($app);

        $controllers->before(function () use ($firewall) {
            $firewall->requireRight(\ACL::CANADDRECORD);
        });

        $controllers->get('/', 'controller.prod.lazaret:listElement')
            ->bind('lazaret_elements');

        $controllers->get('/{file_id}/', 'controller.prod.lazaret:getElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_element');

        $controllers->post('/{file_id}/force-add/', 'controller.prod.lazaret:addElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_force_add');

        $controllers->post('/{file_id}/deny/', 'controller.prod.lazaret:denyElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_deny_element');

        $controllers->post('/empty/', 'controller.prod.lazaret:emptyLazaret')
            ->bind('lazaret_empty');

        $controllers->post('/{file_id}/accept/', 'controller.prod.lazaret:acceptElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_accept');

        $controllers->get('/{file_id}/thumbnail/', 'controller.prod.lazaret:thumbnailElement')
            ->assert('file_id', '\d+')
            ->bind('lazaret_thumbnail');

        return $controllers;
    }
}
