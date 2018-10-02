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
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\DoDownloadController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class DoDownload implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.do-download'] = $app->share(function (PhraseaApplication $app) {
            return (new DoDownloadController($app))
                ->setDelivererLocator(new LazyLocator($app, 'phraseanet.file-serve'))
                ->setDispatcher($app['dispatcher'])
                ->setFileSystemLocator(new LazyLocator($app, 'filesystem'))
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
        $controllers = $this->createCollection($app);

        $controllers->get('/{token}/prepare/', 'controller.prod.do-download:prepareDownload')
            ->before($app['middleware.token.converter'])
            ->bind('prepare_download')
            ->assert('token', '[a-zA-Z0-9]{8,32}');

        $controllers->match('/{token}/get/', 'controller.prod.do-download:downloadDocuments')
            ->before($app['middleware.token.converter'])
            ->bind('document_download')
            ->assert('token', '[a-zA-Z0-9]{8,32}');

        $controllers->post('/{token}/execute/', 'controller.prod.do-download:downloadExecute')
            ->before($app['middleware.token.converter'])
            ->bind('execute_download')
            ->assert('token', '[a-zA-Z0-9]{8,32}');

        return $controllers;
    }
}
