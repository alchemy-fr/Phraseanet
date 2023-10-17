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
use Alchemy\Phrasea\Controller\Prod\DownloadController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Download implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.download'] = $app->share(function (PhraseaApplication $app) {
            return (new DownloadController($app))
                ->setDispatcher($app['dispatcher']);
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
        $controllers->before(new OAuthListener(['exit_not_present' => false]));
        $this->getFirewall($app)->addMandatoryAuthentication($controllers);

        /** @uses DownloadController::listDownloadAsync */
        $controllers->post('/list_async/', 'controller.prod.download:listDownloadAsync')
            ->bind('list_download_async');

        /** @uses DownloadController::startDownloadAsync */
        $controllers->post('/start_async/', 'controller.prod.download:startDownloadAsync')
            ->bind('start_download_async');

        /** @uses DownloadController::checkDownload */
        $controllers->post('/', 'controller.prod.download:checkDownload')
            ->bind('check_download');

        return $controllers;
    }
}
