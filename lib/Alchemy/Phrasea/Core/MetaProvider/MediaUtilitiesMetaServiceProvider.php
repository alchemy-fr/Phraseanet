<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Core\Provider\MediaAlchemystServiceProvider as PhraseanetMediaAlchemystServiceProvider;
use FFMpeg\FFMpegServiceProvider;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use MP4Box\MP4BoxServiceProvider;
use Neutron\Silex\Provider\ImagineServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use PHPExiftool\Reader;
use PHPExiftool\Writer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

class MediaUtilitiesMetaServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{

    public function register(Container $app)
    {
        $app->register(new ImagineServiceProvider());
        $app->register(new FFMpegServiceProvider());
        $app->register(new MediaAlchemystServiceProvider());
        $app->register(new PhraseanetMediaAlchemystServiceProvider());
        $app->register(new MediaVorusServiceProvider());
        $app->register(new MP4BoxServiceProvider());
        $app->register(new PHPExiftoolServiceProvider());

        $app['imagine.factory'] = function (Application $app) {
            if ($app['conf']->get(['registry', 'executables', 'imagine-driver']) != '') {
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
    }

    public function boot(Application $app)
    {
        if(isset($app['exiftool.reader']) && isset($app['conf'])) {
            try {
                $timeout = $app['conf']->get(['main', 'binaries', 'exiftool_timeout'], 60);

                /** @var Reader $exiftoolReader */
                $exiftoolReader = $app['exiftool.reader'];
                $exiftoolReader->setTimeout($timeout);

                /** @var Writer $exiftoolWriter */
                $exiftoolWriter = $app['exiftool.writer'];
                $exiftoolWriter->setTimeout($timeout);
            }
            catch(\Exception $e) {
                // no-nop
            }
        }
    }
}
