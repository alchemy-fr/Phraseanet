<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

class Tooltip implements ControllerProviderInterface
{
    public function connect(Application $app)
    {
        $app['controller.prod.tooltip'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireAuthentication();
        });

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

    public function displayBasket(Application $app, Basket $basket)
    {
        return $app['twig']->render('prod/Tooltip/Basket.html.twig', ['basket' => $basket]);
    }

    public function displayStory(Application $app, $sbas_id, $record_id)
    {
        $Story = new \record_adapter($app, $sbas_id, $record_id);

        return $app['twig']->render('prod/Tooltip/Story.html.twig', ['Story' => $Story]);
    }

    public function displayUserBadge(Application $app, $usr_id)
    {
        $user = \User_Adapter::getInstance($usr_id, $app);

        return $app['twig']->render(
                'prod/Tooltip/User.html.twig'
                , ['user' => $user]
        );
    }

    public function displayPreview(Application $app, $sbas_id, $record_id)
    {
        $record = new \record_adapter($app, $sbas_id, $record_id);

        return $app['twig']->render(
                'prod/Tooltip/Preview.html.twig'
                , ['record'      => $record, 'not_wrapped' => true]
        );
    }

    public function displayCaption(Application $app, $sbas_id, $record_id, $context)
    {
        $number = (int) $app['request']->get('number');
        $record = new \record_adapter($app, $sbas_id, $record_id, $number);

        $search_engine = $app['phraseanet.SE'];

        if ($context == 'answer') {
            try {
                $search_engine_options = SearchEngineOptions::hydrate($app, $app['request']->request->get('options_serial'));
                $search_engine->setOptions($search_engine_options);
            } catch (\Exception $e) {

            }
        }

        return $app['twig']->render(
            'prod/Tooltip/Caption.html.twig'
            , [
            'record'       => $record,
            'view'         => $context,
            'highlight'    => $app['request']->request->get('query'),
            'searchEngine' => $search_engine,
        ]);
    }

    public function displayTechnicalDatas(Application $app, $sbas_id, $record_id)
    {
        $record = new \record_adapter($app, $sbas_id, $record_id);

        try {
            $document = $record->get_subdef('document');
        } catch (\Exception $e) {
            $document = null;
        }

        return $app['twig']->render(
                'prod/Tooltip/TechnicalDatas.html.twig'
                , ['record'   => $record, 'document' => $document]
        );
    }

    public function displayFieldInfos(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
                'prod/Tooltip/DataboxField.html.twig'
                , ['field' => $field]
        );
    }

    public function displayDCESInfos(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
                'prod/Tooltip/DCESFieldInfo.html.twig'
                , ['field' => $field]
        );
    }

    public function displayMetaRestrictions(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
                'prod/Tooltip/DataboxFieldRestrictions.html.twig'
                , ['field' => $field]
        );
    }
}
