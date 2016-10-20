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
use Alchemy\Phrasea\Controller\Prod\UploadController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Upload implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.upload'] = $app->share(function (PhraseaApplication $app) {
            return (new UploadController($app))
                ->setBorderManagerLocator(new LazyLocator($app, 'border-manager'))
                ->setDispatcher($app['dispatcher'])
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setEntityManagerLocator(new LazyLocator($app, 'orm.em'))
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
                ->setTemporaryFileSystemLocator(new LazyLocator($app, 'temporary-filesystem'))
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

        $controllers->get('/', 'controller.prod.upload:getUploadForm')
            ->bind('upload_form');

        $controllers->get('/flash-version/', 'controller.prod.upload:getFlashUploadForm')
            ->bind('upload_flash_form');
        $controllers->get('/html5-version/', 'controller.prod.upload:getHtml5UploadForm')
            ->bind('upload_html5_form');

        $controllers->post('/', 'controller.prod.upload:upload')
            ->bind('upload');

        return $controllers;
    }
}
