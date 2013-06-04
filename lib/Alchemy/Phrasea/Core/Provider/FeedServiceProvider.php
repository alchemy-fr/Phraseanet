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

use Alchemy\Phrasea\Feed\LinkGenerator;
use Alchemy\Phrasea\Feed\AggregateLinkGenerator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class FeedServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['feed.user-link-generator'] = $app->share(function($app) {
            return new LinkGenerator($app['url_generator'], $app['EM'], $app['tokens']);
        });
        $app['feed.aggregate-link-generator'] = $app->share(function($app) {
            return new AggregateLinkGenerator($app['url_generator'], $app['EM'], $app['tokens']);
        });
    }

    public function boot(Application $app)
    {
    }
}
