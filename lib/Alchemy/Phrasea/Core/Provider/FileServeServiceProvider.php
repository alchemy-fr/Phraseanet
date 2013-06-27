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

use Silex\Application;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Response\ServeFileResponseFactory;
use Alchemy\Phrasea\Core\Event\Subscriber\XSendFileSubscriber;

class FileServeServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['xsendfile.mapping'] = $app->share(function(Application $app) {
            $mapping = array();

            if (isset($app['phraseanet.configuration']['xsendfile']['mapping'])) {
                $mapping = $app['phraseanet.configuration']['xsendfile']['mapping'];
            }

            $mapping[] = array(
                'directory' => $app['root.path'] . '/tmp/download/',
                'mount-point' => '/download/',
            );
            $mapping[] = array(
                'directory' => $app['root.path'] . '/tmp/lazaret/',
                'mount-point' => '/lazaret/',
            );

            return $mapping;
        });

        $app['phraseanet.xsendfile-mapping'] = $app->share(function($app) {
            return new Mapping($app['xsendfile.mapping']);
        });

        $app['phraseanet.file-serve'] = $app->share(function (Application $app) {
            return ServeFileResponseFactory::create($app);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
        $app['dispatcher']->addSubscriber(new XSendFileSubscriber($app));
    }
}
