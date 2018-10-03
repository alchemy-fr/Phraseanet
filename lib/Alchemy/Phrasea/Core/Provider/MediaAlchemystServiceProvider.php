<?php

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class MediaAlchemystServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['media-alchemyst.configuration'] = $app->share(function (Application $app) {
            $configuration = [];
            $parameters = [
                'swftools.pdf2swf.binaries'    => 'pdf2swf_binary',
                'swftools.swfrender.binaries'  => 'swf_render_binary',
                'swftools.swfextract.binaries' => 'swf_extract_binary',
                'unoconv.binaries'             => 'unoconv_binary',
                'mp4box.binaries'              => 'mp4box_binary',
                'gs.binaries'                  => 'ghostscript_binary',
                'ffmpeg.ffmpeg.binaries'       => 'ffmpeg_binary',
                'ffmpeg.ffprobe.binaries'      => 'ffprobe_binary',
                'ffmpeg.ffmpeg.timeout'        => 'ffmpeg_timeout',
                'ffmpeg.ffprobe.timeout'       => 'ffprobe_timeout',
                'gs.timeout'                   => 'gs_timeout',
                'mp4box.timeout'               => 'mp4box_timeout',
                'swftools.timeout'             => 'swftools_timeout',
                'unoconv.timeout'              => 'unoconv_timeout',
            ];

            foreach ($parameters as $parameter => $key) {
                if ($app['conf']->has(['main', 'binaries', $key])) {
                    $configuration[$parameter] = $app['conf']->get(['main', 'binaries', $key]);
                }
            }

            $configuration['ffmpeg.threads'] = $app['conf']->get(['registry', 'executables', 'ffmpeg-threads']) ?: null;
            $configuration['imagine.driver'] = $app['conf']->get(['registry', 'executables', 'imagine-driver']) ?: null;

            return $configuration;
        });

        $app['media-alchemyst.logger'] = $app->share(function (Application $app) {
            return $app['monolog'];
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
