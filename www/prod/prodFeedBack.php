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

/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();

$user = $Core->getAuthenticatedUser();

$output = '';

$request = http_request::getInstance();
$parm = $request->get_parms('action');

$action = $parm['action'];

switch ($action)
{
  case 'search':
    $engine = new searchEngine_adapter_sphinx_engine();

    $parm = $request->get_parms("bas", "term"
            , "stemme"
            , "search_type", "recordtype", "status", "fields", "datemin", "datemax", "datefield");

    $options = new searchEngine_options();

    $options->set_bases($parm['bas'], $user->ACL());
    if (!is_array($parm['fields']))
      $parm['fields'] = array();
    $options->set_fields($parm['fields']);
    if (!is_array($parm['status']))
      $parm['status'] = array();
    $options->set_status($parm['status']);
    $options->set_search_type($parm['search_type']);
    $options->set_record_type($parm['recordtype']);
    $options->set_min_date($parm['datemin']);
    $options->set_max_date($parm['datemax']);
    $options->set_date_fields(explode('|', $parm['datefield']));
    $options->set_use_stemming($parm['stemme']);


    $engine->set_options($options);
    $result = $engine->results($parm['term'], 0, 1);
    $res = $engine->get_suggestions($session, true);
//    $res = array(array('id'=>'oui','label'=>'oui','value'=>'oui'));
    $output = p4string::jsonencode($res);

    break;

  case 'CSS':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('color');
    $output = $user->setPrefs('css', $parm['color']);
    break;

  case 'SAVETEMPPREF':
    $parm = $request->get_parms('prop', 'value');
    $session->set_session_prefs($parm['prop'], $parm['value']);
    $output = 1;
    break;

  case 'DELETE':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('lst');
    $output = whatCanIDelete($parm['lst']);
    break;
  case 'DODELETE':
    require ($registry->get('GV_RootPath') . 'lib/classes/deprecated/prodUtils.php');
    $parm = $request->get_parms('lst', 'del_children');
    $output = deleteRecord($parm['lst'], $parm['del_children']);
    break;

  case 'READ_NOTIFICATIONS':
    try
    {
      $evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);
      $parm = $request->get_parms('notifications');
      $output = $evt_mngr->read(explode('_', $parm['notifications']), $session->get_usr_id());
      $output = p4string::jsonencode(array('error' => false, 'message' => ''));
    }
    catch (Exception $e)
    {
      $output = p4string::jsonencode(array('error' => true, 'message' => $e->getMessage()));
    }
    break;
  case 'NOTIFICATIONS_FULL':
    $evt_mngr = eventsmanager_broker::getInstance($appbox, $Core);
    $parm = $request->get_parms('page');
    $output = $evt_mngr->get_json_notifications($parm['page']);
    break;





  case 'VIDEOTOKEN':
    $parm = $request->get_parms('sbas_id', 'record_id');
    $sbas_id = (int) $parm['sbas_id'];
    $record = new record_adapter($sbas_id, $parm['record_id']);

    $output = p4string::jsonencode(array('url' => $record->get_preview()->renew_url()));
    break;



  case 'ANSWERTRAIN':
    $parm = $request->get_parms('pos', 'record_id', 'options_serial', 'query');

    $search_engine = null;
    if (($options = unserialize($parm['options_serial'])) !== false)
    {
      $search_engine = new searchEngine_adapter($registry);
      $search_engine->set_options($options);
    }

    $record = new record_preview('RESULT', $parm['pos'], '', '', $search_engine, $parm['query']);
    $records = $record->get_train($parm['pos'], $parm['query'], $search_engine);
    $core = \bootstrap::getCore();
    $twig = $core->getTwig();
    $output = p4string::jsonencode(
                    array('current' =>
                        $twig->render(
                                'prod/preview/result_train.html', array(
                            'records' => $records
                            , 'selected' => $parm['pos'])
                        )
                    )
    );
    break;


  case 'REGTRAIN':
    $parm = $request->get_parms('cont', 'pos');
    $record = new record_preview('REG', $parm['pos'], $parm['cont']);
    $output = $twig->render('prod/preview/reg_train.html', array('container_records' => $record->get_container()->get_children(),
        'record' => $record));
    break;

  case 'GET_ORDERMANAGER':
    try
    {
      $parm = $request->get_parms('sort', 'page');
      $orders = new set_ordermanager($parm['sort'], $parm['page']);

      $core = \bootstrap::getCore();
      $twig = $core->getTwig();

      $render = $twig->render('prod/orders/order_box.twig', array('ordermanager' => $orders));
      $ret = array('error' => false, 'datas' => $render);
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;

  case 'GET_ORDER':
    try
    {
      $parm = $request->get_parms('order_id');
      $order = new set_order($parm['order_id']);

      $core = \bootstrap::getCore();
      $twig = $core->getTwig();

      $render = $twig->render('prod/orders/order_item.twig', array('order' => $order));
      $ret = array('error' => false, 'datas' => $render);
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;

  case 'SEND_ORDER':
    try
    {
      $parm = $request->get_parms('order_id', 'elements', 'force');
      $order = new set_order($parm['order_id']);
      $order->send_elements($parm['elements'], $parm['force']);
      $ret = array('error' => false, 'datas' => '');
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;

  case 'DENY_ORDER':
    try
    {
      $parm = $request->get_parms('order_id', 'elements');
      $order = new set_order($parm['order_id']);
      $order->deny_elements($parm['elements']);
      $ret = array('error' => false, 'datas' => '');
    }
    catch (Exception $e)
    {
      $ret = array('error' => true, 'datas' => $e->getMessage());
    }

    $output = p4string::jsonencode($ret);
    break;



  case "ORDER":
    $parm = $request->get_parms('lst', 'ssttid', 'use', 'deadline');
    $order = new set_exportorder($parm['lst'], $parm['ssttid']);

    if ($order->order_available_elements($session->get_usr_id(), $parm['use'], $parm['deadline']))
    {
      $ret = array('error' => false, 'message' => _('les enregistrements ont ete correctement commandes'));
    }
    else
    {
      $ret = array('error' => true, 'message' => _('Erreur lors de la commande des enregistrements'));
    }

    $output = p4string::jsonencode($ret);

    break;
  case "FTP_EXPORT":

    $request = http_request::getInstance();
    $parm = $request->get_parms(
            "addr"   // addr du srv ftp
            , "login" // login ftp
            , "pwd"  // pwd ftp
            , "passif" // mode passif ou non
            , "nbretry" // nb retry
            , "ssl" // nb retry
            , "obj" // les types d'obj a exporter
            , "destfolder"// le folder de destination
            , "usr_dest"  // le mail dudestinataire ftp
            , "lst"  // la liste des objets
            , "ssttid"
            , "sendermail"
            , "namecaract"
            , "NAMMKDFOLD"
            , "logfile"
    );

    $download = new set_exportftp($parm['lst'], $parm['ssttid']);

    if (count($download->get_display_ftp()) == 0)
    {
      $output = p4string::jsonencode(array('error' => true, 'message' => _('Les documents ne peuvent etre envoyes par FTP')));
    }
    else
    {
      try
      {
        $download->prepare_export($parm['obj']);
        $download->export_ftp($parm['usr_dest'], $parm['addr'], $parm['login'], $parm['pwd'], $parm['ssl'], $parm['nbretry'], $parm['passif'], $parm['destfolder'], $parm['NAMMKDFOLD'], $parm['logfile']);

        $output = p4string::jsonencode(array('error' => false, 'message' => _('Export enregistre dans la file dattente')));
      }
      catch (Exception $e)
      {
        $output = p4string::jsonencode(array('error' => true, 'message' => $e->getMessage()));
      }
    }
    break;
  case "FTP_TEST":

    $request = http_request::getInstance();
    $parm = $request->get_parms(
            "addr"   // addr du srv ftp
            , "login" // login ftp
            , "pwd"  // pwd ftp
            , "ssl" // nb retry
    );

    $ssl = $parm['ssl'] == '1';

    try
    {
      $ftp_client = new ftpclient($parm['addr'], 21, 90, $ssl = false);
      $ftp_client->login($parm['login'], $parm['pwd']);
      $ftp_client->close();
      $output = _('Connection au FTP avec succes');
    }
    catch (Exception $e)
    {
      $output = sprintf(_('Erreur lors de la connection au FTP : %s'), $e->getMessage());
    }

    break;
}
echo $output;


