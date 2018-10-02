<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Feed\Formatter\AtomFormatter;
use Alchemy\Phrasea\Feed\Formatter\CoolirisFormatter;
use Alchemy\Phrasea\Feed\Formatter\RssFormatter;
use Alchemy\Phrasea\Feed\Link\AggregateLinkGenerator;
use Alchemy\Phrasea\Feed\Link\FeedLinkGenerator;
use Alchemy\Phrasea\Feed\Link\LinkGeneratorCollection;
use Silex\Application;
use Silex\ServiceProviderInterface;

class FeedServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['feed.user-link-generator'] = $app->share(function ($app) {
            return new FeedLinkGenerator($app['url_generator'], $app['orm.em'], $app['random.low']);
        });
        $app['feed.aggregate-link-generator'] = $app->share(function ($app) {
            return new AggregateLinkGenerator($app['url_generator'], $app['orm.em'], $app['random.medium']);
        });
        $app['feed.link-generator-collection'] = $app->share(function ($app) {
            $collection = new LinkGeneratorCollection();
            $collection->pushGenerator($app['feed.user-link-generator']);
            $collection->pushGenerator($app['feed.aggregate-link-generator']);

            return $collection;
        });
        $app['feed.rss-formatter'] = $app->share(function ($app) {
            return new RssFormatter($app['feed.link-generator-collection']);
        });
        $app['feed.atom-formatter'] = $app->share(function ($app) {
            return new AtomFormatter($app['feed.link-generator-collection']);
        });
        $app['feed.cooliris-formatter'] = $app->share(function ($app) {
            return new CoolirisFormatter($app['feed.link-generator-collection']);
        });
        $app['feed.formatter-strategy'] = $app->protect(function ($type) use ($app) {
            switch ($type) {
                case 'rss':
                    return $app['feed.rss-formatter'];
                    break;
                case 'atom':
                    return $app['feed.atom-formatter'];
                    break;
                case 'cooliris':
                    return $app['feed.cooliris-formatter'];
                    break;
                default:
                    throw new InvalidArgumentException(sprintf('Format %s is not recognized.', $type));
            }
        });
    }

    public function boot(Application $app)
    {
    }
}
