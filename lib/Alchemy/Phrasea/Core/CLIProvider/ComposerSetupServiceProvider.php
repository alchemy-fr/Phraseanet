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
use GuzzleHttp\Client as Guzzle;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class ComposerSetupServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['composer-setup.guzzle'] = function (Application $app) {
           return new Guzzle();
        };
        $app['composer-setup'] = function (Application $app) {
           return new ComposerSetup($app['composer-setup.guzzle']);
        };
    }
}
