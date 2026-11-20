<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Databox\DataboxService;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class DataboxServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        $app['databox.service'] = function (PhraseaApplication $app) {
            return new DataboxService(
                $app,
                $app->getApplicationBox(),
                $app['dbal.provider'],
                $app['repo.databoxes'],
                $app['conf'],
                $app['root.path']
            );
        };
    }
}
