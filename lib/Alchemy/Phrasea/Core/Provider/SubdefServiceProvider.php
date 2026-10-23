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

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Media\SubdefSubstituer;
use Pimple\ServiceProviderInterface;
use Alchemy\Phrasea\Media\SubdefGenerator;
use Pimple\Container;

class SubdefServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['subdef.generator'] = function (Application $app) {
            $generator = new SubdefGenerator($app, $app['media-alchemyst'], $app['phraseanet.filesystem'], $app['mediavorus'], isset($app['task-manager.logger']) ? $app['task-manager.logger'] : $app['monolog']);
            $generator->setDispatcher($app['dispatcher']);

            return $generator;
        };
        $app['subdef.substituer'] = function (Application $app) {
            return new SubdefSubstituer($app, $app['phraseanet.filesystem'], $app['media-alchemyst'], $app['mediavorus'], $app['dispatcher']);
        };
    }
}
