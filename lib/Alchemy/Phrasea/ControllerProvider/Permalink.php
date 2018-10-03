<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\PermalinkController;
use Alchemy\Phrasea\Core\Event\Listener\OAuthListener;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Permalink implements ControllerProviderInterface, ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['controller.permalink'] = $app->share(function (PhraseaApplication $app) {
            return (new PermalinkController(
                $app,
                $app['acl'],
                $app->getAuthenticator(),
                $app['alchemy_embed.service.media']
            ))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
                ->setDelivererLocator(new LazyLocator($app, 'phraseanet.file-serve'))
                ->setApplicationBox($app['phraseanet.appbox'])
            ;
        });
    }

    public function boot(Application $app)
    {
    }

    public function connect(Application $app)
    {
        /** @var ControllerCollection $controllers */
        $controllers = $app['controllers_factory'];

        $controllers
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+');

        $controllers->get('/v1/{sbas_id}/{record_id}/caption/', 'controller.permalink:deliverCaption')
            ->bind('permalinks_caption');

        $controllers->match('/v1/{sbas_id}/{record_id}/caption/', 'controller.permalink:getOptionsResponse')
            ->method('OPTIONS');

        $controllers->get('/v1/{sbas_id}/{record_id}/{subdef}/', 'controller.permalink:deliverPermaview')
            ->bind('permalinks_permaview');

        $controllers->match('/v1/{sbas_id}/{record_id}/{subdef}/', 'controller.permalink:getOptionsResponse')
            ->method('OPTIONS');

        $controllers->get(
            '/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/view/',
            'controller.permalink:deliverPermaviewOldWay'
        )
            ->bind('permalinks_permaview_old');

        $controllers->get('/v1/{sbas_id}/{record_id}/{subdef}/{label}', 'controller.permalink:deliverPermalink')
            ->before(new OAuthListener(['exit_not_present' => false]))
            ->bind('permalinks_permalink');

        $controllers->match('/v1/{sbas_id}/{record_id}/{subdef}/{label}', 'controller.permalink:getOptionsResponse')
            ->method('OPTIONS');

        $controllers->get(
            '/v1/{label}/{sbas_id}/{record_id}/{token}/{subdef}/',
            'controller.permalink:deliverPermalinkOldWay'
        )
            ->bind('permalinks_permalink_old');

        return $controllers;
    }
}
