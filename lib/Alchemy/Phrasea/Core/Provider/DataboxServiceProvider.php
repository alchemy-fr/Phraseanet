<?php

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Databox\DataboxService;
use Silex\Application;
use Silex\ServiceProviderInterface;

class DataboxServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['databox.service'] = $app->share(function (PhraseaApplication $app) {
            return new DataboxService(
                $app,
                $app->getApplicationBox(),
                $app['dbal.provider'],
                $app['repo.databoxes'],
                $app['conf'],
                $app['root.path']
            );
        });
    }

    public function boot(Application $app)
    {
        // TODO: Implement boot() method.
    }
}
