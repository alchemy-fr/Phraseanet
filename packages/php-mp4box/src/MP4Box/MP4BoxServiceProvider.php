<?php

/*
 * This file is part of PHP-MP4Box.
 *
 * (c) Alchemy <info@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MP4Box;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MP4BoxServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['mp4box.default.configuration'] = array(
            'mp4box.binaries' => array('MP4Box'),
            'timeout'         => 60,
        );
        $app['mp4box.configuration'] = array();
        $app['mp4box.logger'] = null;

        $app['mp4box'] = function (Container $app) {
            $configuration = array_replace(
                $app['mp4box.default.configuration'], $app['mp4box.configuration']
            );

            return MP4Box::create($configuration, $app['mp4box.logger']);
        };
    }
}
