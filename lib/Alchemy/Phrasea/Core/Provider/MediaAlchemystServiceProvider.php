<?php

namespace Alchemy\Phrasea\Core\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class MediaAlchemystServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app['media-alchemyst.configuration'] = function (Application $app) {
            $configuration = [];
            $parameters = [
                'unoconv.binaries'             => 'unoconv_binary',
                'mp4box.binaries'              => 'mp4box_binary',
                'gs.binaries'                  => 'ghostscript_binary',
                'ffmpeg.ffmpeg.binaries'       => 'ffmpeg_binary',
                'ffmpeg.ffprobe.binaries'      => 'ffprobe_binary',
                'ffmpeg.ffmpeg.timeout'        => 'ffmpeg_timeout',
                'ffmpeg.ffprobe.timeout'       => 'ffprobe_timeout',
                'gs.timeout'                   => 'gs_timeout',
                'mp4box.timeout'               => 'mp4box_timeout',
                'unoconv.timeout'              => 'unoconv_timeout',
            ];

            foreach ($parameters as $parameter => $key) {
                if ($app['conf']->has(['main', 'binaries', $key])) {
                    $configuration[$parameter] = $app['conf']->get(['main', 'binaries', $key]);
                }
            }

            $configuration['ffmpeg.threads'] = $app['conf']->get(['registry', 'executables', 'ffmpeg-threads']) ?: null;
            $configuration['imagine.driver'] = $app['conf']->get(['registry', 'executables', 'imagine-driver']) ?: null;
            $configuration['exiftool.binary'] = $app['exiftool.binary'];
            $configuration['exiftool.classes-directory'] = $app['exiftool.classes-directory'];

            return $configuration;
        };

        $app['media-alchemyst.logger'] = function (Application $app) {
            return $app['monolog'];
        };
    }
}
