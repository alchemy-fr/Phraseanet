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

class FileServeServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function register(Application $app)
    {
        $app['phraseanet.file-serve'] = $app->share(function (Application $app) {
            return ServeFileResponseFactory::create($app);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function boot(Application $app)
    {
    }
}
