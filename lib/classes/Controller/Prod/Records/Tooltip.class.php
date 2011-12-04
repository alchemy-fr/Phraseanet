<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Controller_Prod_Records_Tooltip implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();
    $app['appbox'] = appbox::get_instance();
    $twig = new supertwig();

    $controllers->post('/basket/{ssel_id}/'
            , function($ssel_id) use ($app)
            {
              $bask = basket_adapter::getInstance($app['appbox'], $ssel_id, $app['appbox']->get_session()->get_usr_id());
              $isReg = false;

              return new Response('<div style="margin:5px;width:280px;"><div><span style="font-weight:bold;font-size:14px;">' .
                              $bask->get_name() . '</span> </div>' .
                              ($isReg ? ('<div style="text-align:right;">' . _('phraseanet::collection') . ' ' . phrasea::bas_names($bask->get_base_id()) . '</div>') : '')
                              . '<div style="margin:5px 0">' . nl2br($bask->get_description()) . '</div>' .
                              '<div style="margin:5px 0;text-align:right;font-style:italic;">' . sprintf(_('paniers: %d elements'), count($bask->get_elements())) .
                              ' - ' . phraseadate::getPrettyString($bask->get_update_date()) . '</div><hr/>
              <div style="position:relative;float:left;width:270px;">' . $bask->get_excerpt() . '</div>');
            })->assert('ssel_id', '\d+');


    $controllers->post('/preview/{sbas_id}/{record_id}/'
            , function($sbas_id, $record_id) use ($app)
            {
              $record = new record_adapter($sbas_id, $record_id);

              $twig = new supertwig();

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
            , function($sbas_id, $record_id, $view) use ($app)
            {
              $number = (int) $app['request']->get('number');
              $record = new record_adapter($sbas_id, $record_id, $number);

              $search_engine = null;
              if (($search_engine_options = unserialize($app['request']->get('options_serial'))) !== false)
              {
                $search_engine = new searchEngine_adapter($app['appbox']->get_registry());
                $search_engine->set_options($search_engine_options);
              }

              $twig = new supertwig();
              $twig->addFilter(array('formatoctet' => 'p4string::format_octets'));

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
            , function($sbas_id, $record_id) use ($app)
            {
              $record = new record_adapter($sbas_id, $record_id);
              $document = $record->get_subdef('document');

              $twig = new supertwig();
              $twig->addFilter(array('formatoctet' => 'p4string::format_octets'));

              return new Response(
                              $twig->render(
                                      'common/technical_datas.twig'
                                      , array('record' => $record, 'document' => $document)
                              )
              );
            })->assert('sbas_id', '\d+')->assert('record_id', '\d+');


    $controllers->post('/metas/FieldInfos/{sbas_id}/{field_id}/'
            , function($sbas_id, $field_id) use ($app)
            {
              $databox = databox::get_instance((int) $sbas_id);
              $field = databox_field::get_instance($databox, $field_id);

              $twig = new supertwig();

              return new Response(
                              $twig->render(
                                      'common/databox_field.twig'
                                      , array('field' => $field)
                              )
              );
            })->assert('sbas_id', '\d+')->assert('field_id', '\d+');


    $controllers->post('/metas/DCESInfos/{sbas_id}/{field_id}/'
            , function($sbas_id, $field_id) use ($app)
            {
              try
              {
                $databox = databox::get_instance((int) $sbas_id);
                $field = databox_field::get_instance($databox, $field_id);

                $twig = new supertwig();

                return new Response(
                                $twig->render(
                                        'common/databox_field_DCES.twig'
                                        , array('field' => $field)
                                )
                );
              }
              catch (Exception $e)
              {
                exit($e->getMessage());
              }
            })->assert('sbas_id', '\d+')->assert('field_id', '\d+');


    $controllers->post('/metas/restrictionsInfos/{sbas_id}/{field_id}/'
            , function($sbas_id, $field_id) use ($app)
            {
              $databox = databox::get_instance((int) $sbas_id);
              $field = databox_field::get_instance($databox, $field_id);

              $twig = new supertwig();

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