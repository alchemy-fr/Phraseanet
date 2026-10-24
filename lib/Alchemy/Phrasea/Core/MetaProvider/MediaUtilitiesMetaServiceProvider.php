<?php

namespace Alchemy\Phrasea\Core\MetaProvider;

use Alchemy\Phrasea\Core\Provider\FFMpegServiceProvider;
use Alchemy\Phrasea\Core\Provider\ImagineServiceProvider;
use Alchemy\Phrasea\Core\Provider\MediaAlchemystServiceProvider as PhraseanetMediaAlchemystServiceProvider;
use Alchemy\Phrasea\Core\Provider\PHPExiftoolServiceProvider;
use MediaAlchemyst\MediaAlchemystServiceProvider;
use MediaVorus\MediaVorusServiceProvider;
use MP4Box\MP4BoxServiceProvider;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MediaUtilitiesMetaServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app->register(new ImagineServiceProvider());
        $app->register(new FFMpegServiceProvider());
        $app->register(new PHPExiftoolServiceProvider());
        $app->register(new MediaAlchemystServiceProvider());
        $app->register(new PhraseanetMediaAlchemystServiceProvider());
        $app->register(new MediaVorusServiceProvider());
        $app->register(new MP4BoxServiceProvider());
    }
}
