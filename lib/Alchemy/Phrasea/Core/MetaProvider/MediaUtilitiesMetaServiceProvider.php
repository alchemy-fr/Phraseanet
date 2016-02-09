<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Core\Provider\MediaAlchemystServiceProvider as PhraseanetMediaAlchemystServiceProvider;
use FFMpeg\FFMpegServiceProvider;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use MP4Box\MP4BoxServiceProvider;
use Neutron\Silex\Provider\ImagineServiceProvider;
use PHPExiftool\PHPExiftoolServiceProvider;
use Silex\Application;
use Silex\ServiceProviderInterface;

class MediaUtilitiesMetaServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app->register(new ImagineServiceProvider());
        $app->register(new FFMpegServiceProvider());
        $app->register(new MediaAlchemystServiceProvider());
        $app->register(new PhraseanetMediaAlchemystServiceProvider());
        $app->register(new MediaVorusServiceProvider());
        $app->register(new MP4BoxServiceProvider());
        $app->register(new PHPExiftoolServiceProvider());

        $app['imagine.factory'] = $app->share(function (Application $app) {
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
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }
}
