<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Core\Service\Builder;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ORMServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['EM'] = $app->share(function(Application $app) {
            return Builder::create(
                    $app, $app['phraseanet.configuration']
                        ->getService($app['phraseanet.configuration']
                            ->getOrm())
                )->getDriver();
        });
    }

    public function boot(Application $app)
    {
    }
}
