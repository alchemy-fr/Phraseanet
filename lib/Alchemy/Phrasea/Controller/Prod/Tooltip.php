<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\Helper\Record as RecordHelper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Tooltip implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();
    $app['appbox'] = \appbox::get_instance();

    $controllers->post('/basket/{basket_id}/'
            , function(Application $app, $basket_id)
            {
              $em = $app['Core']->getEntityManager();

              $basket = $em->getRepository('\Entities\Basket')
                      ->findUserBasket($basket_id, $app['Core']->getAuthenticatedUser());

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render('prod/Tooltip/Basket.html.twig', array('basket' => $basket));
            })->assert('basket_id', '\d+');

    $controllers->post('/Story/{sbas_id}/{record_id}/'
            , function(Application $app, $sbas_id, $record_id)
            {
              $Story = new \record_adapter($sbas_id, $record_id);

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return $twig->render('prod/Tooltip/Story.html.twig', array('Story' => $Story));
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post('/user/{usr_id}/'
            , function(Application $app, $usr_id)
            {
              $user = \User_Adapter::getInstance($usr_id, \appbox::get_instance());

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response($twig->render(
                                      'prod/Tooltip/User.html.twig'
                                      , array('user' => $user)
                              )
              );
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post('/preview/{sbas_id}/{record_id}/'
            , function(Application $app, $sbas_id, $record_id)
            {
              $record = new \record_adapter($sbas_id, $record_id);

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response($twig->render(
                                      'common/preview.html'
                                      , array(
                                  'record' => $record
                                  , 'not_wrapped' => true
                                      )
                              )
              );
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post('/caption/{sbas_id}/{record_id}/{view}/'
            , function(Application $app, $sbas_id, $record_id, $view)
            {
              $number = (int) $app['request']->get('number');
              $record = new \record_adapter($sbas_id, $record_id, $number);

              $search_engine = null;
              if (($search_engine_options = unserialize($app['request']->get('options_serial'))) !== false)
              {
                $search_engine = new \searchEngine_adapter($app['appbox']->get_registry());
                $search_engine->set_options($search_engine_options);
              }

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response(
                              $twig->render(
                                      'common/caption.html'
                                      , array(
                                  'record' => $record
                                  , 'view' => $view
                                  , 'highlight' => $app['request']->get('query')
                                  , 'searchEngine' => $search_engine
                                      )
                              )
              );
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post('/tc_datas/{sbas_id}/{record_id}/'
            , function(Application $app, $sbas_id, $record_id)
            {
              $record = new \record_adapter($sbas_id, $record_id);
              $document = $record->get_subdef('document');

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response(
                              $twig->render(
                                      'common/technical_datas.twig'
                                      , array('record' => $record, 'document' => $document)
                              )
              );
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post('/metas/FieldInfos/{sbas_id}/{field_id}/'
            , function(Application $app, $sbas_id, $field_id)
            {
              $databox = \databox::get_instance((int) $sbas_id);
              $field = \databox_field::get_instance($databox, $field_id);

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response(
                              $twig->render(
                                      'common/databox_field.twig'
                                      , array('field' => $field)
                              )
              );
            })->assert('sbas_id', '\d+')->assert('field_id', '\d+');


    $controllers->post('/metas/DCESInfos/{sbas_id}/{field_id}/'
            , function(Application $app, $sbas_id, $field_id)
            {
              try
              {
                $databox = \databox::get_instance((int) $sbas_id);
                $field = \databox_field::get_instance($databox, $field_id);

                /* @var $twig \Twig_Environment */
                $twig = $app['Core']->getTwig();

                return new Response(
                                $twig->render(
                                        'common/databox_field_DCES.twig'
                                        , array('field' => $field)
                                )
                );
              }
              catch (\Exception $e)
              {
                exit($e->getMessage());
              }
            })->assert('sbas_id', '\d+')->assert('field_id', '\d+');


    $controllers->post('/metas/restrictionsInfos/{sbas_id}/{field_id}/'
            , function(Application $app, $sbas_id, $field_id)
            {
              $databox = \databox::get_instance((int) $sbas_id);
              $field = \databox_field::get_instance($databox, $field_id);

              /* @var $twig \Twig_Environment */
              $twig = $app['Core']->getTwig();

              return new Response(
                              $twig->render(
                                      'common/databox_field_restrictions.twig'
                                      , array('field' => $field)
                              )
              );
            })->assert('sbas_id', '\d+')->assert('field_id', '\d+');

    return $controllers;
  }

}