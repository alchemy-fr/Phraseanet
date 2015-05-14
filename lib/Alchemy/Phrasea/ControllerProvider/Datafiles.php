<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\DatafileController;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class Datafiles implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controller.datafiles'] = $app->share(function (PhraseaApplication $app) {
            return (new DatafileController($app, $app['phraseanet.appbox'], $app['acl'], $app['authentication']))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDelivererLocator(function () use ($app) {
                    return $app['phraseanet.file-serve'];
                });
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            if (!$app['authentication']->isAuthenticated()) {
                $app->abort(403, sprintf('You are not authorized to access %s', $request->getRequestUri()));
            }
        });

        $controllers->get('/{sbas_id}/{record_id}/{subdef}/', 'controller.datafiles:getAction')
            ->bind('datafile')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        return $controllers;
    }
}
