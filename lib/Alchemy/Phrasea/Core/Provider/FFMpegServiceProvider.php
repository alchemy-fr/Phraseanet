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

use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class FFMpegServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        /**
         * Keys: ffmpeg.binaries, ffmpeg.timeout, ffmpeg.threads, ffprobe.binaries, ffprobe.timeout
         * (a null value lets php-ffmpeg use its default)
         */
        $app['ffmpeg.configuration'] = function (Container $app) {
            $configuration = [];

            if (!isset($app['conf'])) {
                return $configuration;
            }

            $parameters = [
                'ffmpeg.binaries'  => 'ffmpeg_binary',
                'ffprobe.binaries' => 'ffprobe_binary',
                'ffmpeg.timeout'   => 'ffmpeg_timeout',
                'ffprobe.timeout'  => 'ffprobe_timeout',
            ];

            foreach ($parameters as $parameter => $key) {
                if ($app['conf']->has(['main', 'binaries', $key])) {
                    $configuration[$parameter] = $app['conf']->get(['main', 'binaries', $key]);
                }
            }

            $configuration['ffmpeg.threads'] = $app['conf']->get(['registry', 'executables', 'ffmpeg-threads']) ?: null;

            return $configuration;
        };

        $app['ffmpeg.logger'] = function (Container $app) {
            return isset($app['monolog']) ? $app['monolog'] : new NullLogger();
        };

        $app['ffmpeg.ffprobe.cache'] = function () {
            return new ArrayAdapter();
        };

        $app['ffmpeg.ffprobe'] = function (Container $app) {
            $configuration = $app['ffmpeg.configuration'];

            return FFProbe::create(array_filter([
                'ffprobe.binaries' => isset($configuration['ffprobe.binaries']) ? $configuration['ffprobe.binaries'] : null,
                'timeout'          => isset($configuration['ffprobe.timeout']) ? $configuration['ffprobe.timeout'] : null,
            ], function ($value) {
                return null !== $value;
            }), $app['ffmpeg.logger'], $app['ffmpeg.ffprobe.cache']);
        };

        $app['ffmpeg.ffmpeg'] = function (Container $app) {
            $configuration = $app['ffmpeg.configuration'];

            return FFMpeg::create(array_filter([
                'ffmpeg.binaries' => isset($configuration['ffmpeg.binaries']) ? $configuration['ffmpeg.binaries'] : null,
                'timeout'         => isset($configuration['ffmpeg.timeout']) ? $configuration['ffmpeg.timeout'] : null,
                'ffmpeg.threads'  => isset($configuration['ffmpeg.threads']) ? $configuration['ffmpeg.threads'] : null,
            ], function ($value) {
                return null !== $value;
            }), $app['ffmpeg.logger'], $app['ffmpeg.ffprobe']);
        };
    }
}
