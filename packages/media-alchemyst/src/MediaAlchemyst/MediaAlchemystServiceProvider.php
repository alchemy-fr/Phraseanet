<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MediaAlchemyst;

use Neutron\TemporaryFilesystem\Manager;
use Neutron\TemporaryFilesystem\TemporaryFilesystem;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Filesystem\Filesystem;

class MediaAlchemystServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['media-alchemyst.configuration'] = array();
        $app['media-alchemyst.logger'] = null;

        $app['media-alchemyst.drivers'] = function (Container $app) {
            $drivers = DriversContainer::create();
            $drivers['configuration'] = $app['media-alchemyst.configuration'];

            if (null !== $logger = $app['media-alchemyst.logger']) {
                $drivers['logger'] = function () use ($logger) {
                    return $logger;
                };
            }

            return $drivers;
        };

        $app['media-alchemyst.filesystem-manager'] = function (Container $app) {
            return new Manager($app['media-alchemyst.temporary-filesystem'], $app['media-alchemyst.filesystem']);
        };

        $app['media-alchemyst.filesystem'] = function (Container $app) {
            return new Filesystem();
        };

        $app['media-alchemyst.temporary-filesystem'] = function (Container $app) {
            return new TemporaryFilesystem($app['media-alchemyst.filesystem']);
        };

        $app['media-alchemyst'] = function (Container $app) {
            return new Alchemyst($app['media-alchemyst.drivers'], $app['media-alchemyst.filesystem-manager']);
        };
    }
}
