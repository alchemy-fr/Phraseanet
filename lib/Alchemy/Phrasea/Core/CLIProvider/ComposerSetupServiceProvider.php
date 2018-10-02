<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\CLIProvider;

use Alchemy\Phrasea\Utilities\ComposerSetup;
use Guzzle\Http\Client as Guzzle;
use Silex\Application;
use Silex\ServiceProviderInterface;

class ComposerSetupServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['composer-setup.guzzle'] = $app->share(function (Application $app) {
           return new Guzzle();
        });
        $app['composer-setup'] = $app->share(function (Application $app) {
           return new ComposerSetup($app['composer-setup.guzzle']);
        });
    }

    public function boot(Application $app)
    {
    }
}
