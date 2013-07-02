<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Event\Subscriber\XSendFileSubscriber;
use Alchemy\Phrasea\Http\ServeFileResponseFactory;
use Alchemy\Phrasea\Http\XSendFile\XSendFileFactory;
use Silex\Application;
use Silex\ServiceProviderInterface;

class FileServeServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['phraseanet.xsendfile-factory'] = $app->share(function($app) {
            return XSendFileFactory::create($app);
        });

        $app['phraseanet.file-serve'] = $app->share(function (Application $app) {
            return ServeFileResponseFactory::create($app);
        });

        $app['dispatcher'] = $app->share(
            $app->extend('dispatcher', function($dispatcher, Application $app){
                $dispatcher->addSubscriber(new XSendFileSubscriber($app));

                return $dispatcher;
            })
        );
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
