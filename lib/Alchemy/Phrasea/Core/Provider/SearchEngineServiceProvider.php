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

use Alchemy\Phrasea\SearchEngine\SearchEngineLogger;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\SearchEngine\SearchEngineInterface;
use Silex\Application;
use Silex\ServiceProviderInterface;

class SearchEngineServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['phraseanet.SE'] = $app->share(function ($app) {

            $engineClass = $app['conf']->get(['main', 'search-engine', 'type']);
            $engineOptions = $app['conf']->get(['main', 'search-engine', 'options']);

            if (!class_exists($engineClass) || $engineClass instanceof SearchEngineInterface) {
                throw new InvalidArgumentException(sprintf('%s is not valid SearchEngineInterface', $engineClass));
            }

            return $engineClass::create($app, $engineOptions);
        });

        $app['phraseanet.SE.logger'] = $app->share(function (Application $app) {
            return new SearchEngineLogger($app);
        });
    }

    public function boot(Application $app)
    {
    }

}
