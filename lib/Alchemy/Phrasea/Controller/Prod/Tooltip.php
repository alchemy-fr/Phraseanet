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

use Alchemy\Phrasea\SearchEngine\SearchEngineOptions;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;
use Silex\ControllerProviderInterface;

/**
 * @todo        Check if a user has access to record before sending the response
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Tooltip implements ControllerProviderInterface
{

    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $app['firewall']->addMandatoryAuthentication($controllers);

        $controllers->post('/basket/{basket_id}/', $this->call('displayBasket'))
            ->assert('basket_id', '\d+')
            ->bind('prod_tooltip_basket');

        $controllers->post('/Story/{sbas_id}/{record_id}/', $this->call('displayStory'))
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_story');

        $controllers->post('/user/{usr_id}/', $this->call('displayUserBadge'))
            ->assert('usr_id', '\d+')
            ->bind('prod_tooltip_user');

        $controllers->post('/preview/{sbas_id}/{record_id}/', $this->call('displayPreview'))
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_preview');

        $controllers->post('/caption/{sbas_id}/{record_id}/{context}/', $this->call('displayCaption'))
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_caption');

        $controllers->post('/tc_datas/{sbas_id}/{record_id}/', $this->call('displayTechnicalDatas'))
            ->assert('sbas_id', '\d+')
            ->assert('record_id', '\d+')
            ->bind('prod_tooltip_technical_data');

        $controllers->post('/metas/FieldInfos/{sbas_id}/{field_id}/', $this->call('displayFieldInfos'))
            ->assert('sbas_id', '\d+')
            ->assert('field_id', '\d+')
            ->bind('prod_tooltip_metadata');

        $controllers->post('/DCESInfos/{sbas_id}/{field_id}/', $this->call('displayDCESInfos'))
            ->assert('sbas_id', '\d+')
            ->assert('field_id', '\d+')
            ->bind('prod_tooltip_dces');

        $controllers->post('/metas/restrictionsInfos/{sbas_id}/{field_id}/', $this->call('displayMetaRestrictions'))
            ->assert('sbas_id', '\d+')
            ->assert('field_id', '\d+')
            ->bind('prod_tooltip_metadata_restrictions');

        return $controllers;
    }

    public function displayBasket(Application $app, $basket_id)
    {
        $basket = $app['EM']->getRepository('\Entities\Basket')
            ->findUserBasket($app, $basket_id, $app['authentication']->getUser(), false);

        return $app['twig']->render('prod/Tooltip/Basket.html.twig', array('basket' => $basket));
    }

    public function displayStory(Application $app, $sbas_id, $record_id)
    {
        $Story = new \record_adapter($app, $sbas_id, $record_id);

        return $app['twig']->render('prod/Tooltip/Story.html.twig', array('Story' => $Story));
    }

    public function displayUserBadge(Application $app, $usr_id)
    {
        $user = \User_Adapter::getInstance($usr_id, $app);

        return $app['twig']->render(
                'prod/Tooltip/User.html.twig'
                , array('user' => $user)
        );
    }

    public function displayPreview(Application $app, $sbas_id, $record_id)
    {
        return $app['twig']->render('prod/Tooltip/Preview.html.twig', array(
            'record' => new \record_adapter($app, $sbas_id, $record_id),
            'not_wrapped' => true
        ));
    }

    public function displayCaption(Application $app, $sbas_id, $record_id, $context)
    {
        $number = (int) $app['request']->get('number');
        $record = new \record_adapter($app, $sbas_id, $record_id, $number);

        $search_engine = null;

        if ($context == 'answer') {
            try {
                $search_engine_options = SearchEngineOptions::hydrate($app, $app['request']->request->get('options_serial'));

                $search_engine = $app['phraseanet.SE'];
                $search_engine->setOptions($search_engine_options);
            } catch (\Exception $e) {
                $search_engine = null;
            }
        }

        return $app['twig']->render(
            'prod/Tooltip/Caption.html.twig'
            , array(
            'record'       => $record,
            'view'         => $context,
            'highlight'    => $app['request']->request->get('query'),
            'searchEngine' => $search_engine,
        ));
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
                , array('record'   => $record, 'document' => $document)
        );
    }

    public function displayFieldInfos(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
                'prod/Tooltip/DataboxField.html.twig'
                , array('field' => $field)
        );
    }

    public function displayDCESInfos(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
                'prod/Tooltip/DCESFieldInfo.html.twig'
                , array('field' => $field)
        );
    }

    public function displayMetaRestrictions(Application $app, $sbas_id, $field_id)
    {
        $databox = $app['phraseanet.appbox']->get_databox((int) $sbas_id);
        $field = \databox_field::get_instance($app, $databox, $field_id);

        return $app['twig']->render(
                'prod/Tooltip/DataboxFieldRestrictions.html.twig'
                , array('field' => $field)
        );
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
