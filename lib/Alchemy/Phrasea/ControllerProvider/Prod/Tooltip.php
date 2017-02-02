<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Controller\Prod\TooltipController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Tooltip implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.tooltip'] = $app->share(function (PhraseaApplication $app) {
            return (new TooltipController($app))
                ->setSearchEngineLocator(new LazyLocator($app, 'phraseanet.SE'))
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createAuthenticatedCollection($app);

        $controllers->post('/basket/{basket}/', 'controller.prod.tooltip:displayBasket')
            ->assert('basket', '\d+')
            ->before($app['middleware.basket.converter'])
            ->before($app['middleware.basket.user-access'])
            ->bind('prod_tooltip_basket');

        $controllers->post('/Story/{sbas_id}/{record_id}/', 'controller.prod.tooltip:displayStory')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_story');

        $controllers->post('/user/{usr_id}/', 'controller.prod.tooltip:displayUserBadge')
            ->assert('usr_id', '\d+')
            ->bind('prod_tooltip_user');

        $controllers->post('/preview/{sbas_id}/{record_id}/', 'controller.prod.tooltip:displayPreview')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_preview');

        $controllers->post('/caption/{sbas_id}/{record_id}/{context}/', 'controller.prod.tooltip:displayCaption')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_caption');

        $controllers->post('/tc_datas/{sbas_id}/{record_id}/', 'controller.prod.tooltip:displayTechnicalDatas')
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_technical_data');

        $controllers->post('/metas/FieldInfos/{sbas_id}/{field_id}/', 'controller.prod.tooltip:displayFieldInfos')
            ->assert('sbas_id', '\d+')
            ->assert('field_id', '\d+')
            ->bind('prod_tooltip_metadata');

        $controllers->post('/DCESInfos/{sbas_id}/{field_id}/', 'controller.prod.tooltip:displayDCESInfos')
            ->assert('sbas_id', '\d+')
            ->assert('field_id', '\d+')
            ->bind('prod_tooltip_dces');

        $controllers->post('/metas/restrictionsInfos/{sbas_id}/{field_id}/', 'controller.prod.tooltip:displayMetaRestrictions')
            ->assert('sbas_id', '\d+')
            ->assert('field_id', '\d+')
            ->bind('prod_tooltip_metadata_restrictions');

        return $controllers;
    }
}
