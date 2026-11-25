<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Imagine\Gd\Imagine as GdImagine;
use Imagine\Gmagick\Imagine as GmagickImagine;
use Imagine\Image\ImagineInterface;
use Imagine\Imagick\Imagine as ImagickImagine;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class ImagineServiceProvider implements ServiceProviderInterface
{
    private static $drivers = [
        'gmagick' => GmagickImagine::class,
        'imagick' => ImagickImagine::class,
        'gd'      => GdImagine::class,
    ];

    public function register(Container $app)
    {
        /**
         * The Imagine driver name: "gmagick", "imagick" or "gd"
         */
        $app['imagine.factory'] = function (Container $app) {
            if (isset($app['conf']) && $app['conf']->get(['registry', 'executables', 'imagine-driver']) != '') {
                return $app['conf']->get(['registry', 'executables', 'imagine-driver']);
            }

            if (class_exists('\Gmagick')) {
                return 'gmagick';
            }

            if (class_exists('\Imagick')) {
                return 'imagick';
            }

            if (extension_loaded('gd')) {
                return 'gd';
            }

            throw new \RuntimeException('No Imagine driver available');
        };

        $app['imagine'] = function (Container $app) {
            $driver = strtolower((string) $app['imagine.factory']);

            if (!isset(self::$drivers[$driver])) {
                throw new \RuntimeException(sprintf('Invalid Imagine driver "%s"', $app['imagine.factory']));
            }

            $imagine = new self::$drivers[$driver]();

            if (!$imagine instanceof ImagineInterface) {
                throw new \RuntimeException(sprintf('Invalid Imagine driver "%s"', $app['imagine.factory']));
            }

            return $imagine;
        };
    }
}
