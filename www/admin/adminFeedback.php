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
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
phrasea::headers(200, false, 'text/html', 'UTF-8', false);

$request = http_request::getInstance();
$appbox = appbox::get_instance();
$registry = $appbox->get_registry();
$parm = $request->get_parms('action', 'position', 'test', 'renew', 'path', 'tests');

$output = '';

switch ($parm['action'])
{
  case 'TREE':
    $output = module_admin::getTree($parm['position']);
    break;
  case 'APACHE':
    if ($parm['test'] == 'success')
      $output = '1';
    else
      $output = '0';
    break;
  case 'SCHEDULERKEY':
    $output = $registry->get('GV_ServerName') . 'admin/runscheduler.php?key=' . urlencode(phrasea::scheduler_key(!!$parm['renew']));
    break;

  case 'TESTPATH':
    $tests = true;
    foreach ($parm['tests'] as $test)
    {
      switch ($test)
      {
        case 'writeable':
          if (!is_writable($parm['path']))
          {
            $tests = false;
          }
          break;
        case 'readable':
        default:
          if (!is_readable($parm['path']))
          {
            $tests = true;
          }
          break;
      }
    }
    $output = p4string::jsonencode(array('results' => ($tests ? '1' : '0')));
    break;

  case 'EMPTYBASE':
    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));
    try
    {
      $sbas_id = (int) $parm['sbas_id'];
      $databox = databox::get_instance($sbas_id);
      $class_name = 'task_period_emptyColl';
      foreach ($databox->get_collections() as $collection)
      {
        $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><tasksettings><base_id>" . $collection->get_base_id() . "</base_id></tasksettings>";

        task_abstract::create($appbox, $class_name, $settings);
      }
    }
    catch (Exception $e)
    {

    }
    break;
  case 'EMPTYCOLL':
    $parm = $request->get_parms(
                    array(
                        "sbas_id" => http_request::SANITIZE_NUMBER_INT
                        , "coll_id" => http_request::SANITIZE_NUMBER_INT
                    )
    );
    try
    {
      $databox = databox::get_instance($parm['sbas_id']);
      $collection = collection::get_from_coll_id($databox, $parm['coll_id']);

      $class_name = 'task_period_emptyColl';
      $settings = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<tasksettings>\n<base_id>" . $collection->get_base_id() . "</base_id></tasksettings>";

      task_abstract::create($appbox, $class_name, $settings);
    }
    catch (Exception $e)
    {

    }
    break;

  case 'SETTASKSTATUS':

    $parm = $request->get_parms("task_id", "status");

    try
    {
      $task_manager = new task_manager($appbox);
      $task = $task_manager->get_task($parm['task_id']);
      $task->set_status($parm["status"]);
    }
    catch (Exception $e)
    {

    }

    break;

  case 'SETSCHEDSTATUS':

    $parm = $request->get_parms('status');

    try
    {
      $task_manager = new task_manager($appbox);

      $task_manager->set_sched_status($parm['status']);
    }
    catch (Exception $e)
    {

    }
    $ret = new DOMDocument("1.0", "UTF-8");
    $ret->standalone = true;
    $ret->preserveWhiteSpace = false;
    $root = $ret->appendChild($ret->createElement("result"));
    $root->appendChild($ret->createCDATASection(var_export($parm, true)));

    $output = $ret->saveXML();
    break;
  case 'RESETTASKCRASHCOUNTER':

    $parm = $request->get_parms("task_id");

    try
    {
      $task_manager = new task_manager($appbox);
      $task = $task_manager->get_task($parm['task_id']);
      $task->reset_crash_counter();
    }
    catch (Exception $e)
    {

    }
    $ret = new DOMDocument("1.0", "UTF-8");
    $ret->standalone = true;
    $ret->preserveWhiteSpace = false;
    $root = $ret->appendChild($ret->createElement("result"));
    $root->appendChild($ret->createCDATASection(var_export($parm, true)));

    $output = $ret->saveXML();


    break;

  case 'CHANGETASK':

    $parm = $request->get_parms('act', 'task_id', "usr");


    $ret = new DOMDocument("1.0", "UTF-8");
    $ret->standalone = true;
    $ret->preserveWhiteSpace = false;
    $root = $ret->appendChild($ret->createElement("result"));
    $root->setAttribute("saved", "0");
    $root->appendChild($ret->createCDATASection(var_export($parm, true)));

    try
    {
      $task_manager = new task_manager($appbox);
      $task = $task_manager->get_task($parm["task_id"]);
      /**
       * @todo checker, cette methode n'est pas implementee partout
       */
      $root->setAttribute("crashed", $task->get_crash_counter());
      if ($task->saveChanges($conn, $parm["task_id"], $row))
        $root->setAttribute("saved", "1");
    }
    catch (Exception $e)
    {

    }

    $output = $ret->saveXML();
    break;

  case 'PINGSCHEDULER':
    $lockdir = $registry->get('GV_RootPath') . 'tmp/locks/';

    $ret = new DOMDocument("1.0", "UTF-8");
    $ret->standalone = true;
    $ret->preserveWhiteSpace = false;
    $root = $ret->appendChild($ret->createElement("result"));
    $root->appendChild($ret->createCDATASection(var_export(array(), true)));

    $h = "";
    $dat = date("H:i:s");


    $root->setAttribute('time', $dat);


    $task_manager = new task_manager($appbox);
    $scheduler_state = $task_manager->get_scheduler_state();

    $schedstatus = $scheduler_state['schedstatus'];
    $schedqdelay = $scheduler_state['schedqdelay'];
    $schedpid = $scheduler_state['schedpid'];

    $root->setAttribute('status', $schedstatus);
    $root->setAttribute('qdelay', $schedqdelay);

    $schedlock = fopen($lockfile = ($lockdir . 'scheduler.lock'), 'a+');
    if (flock($schedlock, LOCK_SH | LOCK_NB) != true)
    {
      $root->setAttribute('locked', '1');
    }
    else
    {
      $root->setAttribute('locked', '0');
    }
    if ($schedpid > 0)
      $root->setAttribute('pid', $schedpid);
    else
      $root->setAttribute('pid', '');

    foreach ($task_manager->get_tasks() as $task)
    {
      $task_node = $root->appendChild($ret->createElement("task"));
      $task_node->setAttribute('id', $task->get_task_id());
      $task_node->setAttribute('status', $task->get_status());
      $task_node->setAttribute('active', $task->is_active());
      $task_node->setAttribute('crashed', $task->get_crash_counter());
      $task_node->setAttribute('completed', $task->get_completed_percentage());

      $task_node->setAttribute('runner', $task->get_runner());
      if ($task->is_running())
      {
        $task_node->setAttribute('running', '1');
        $task_node->setAttribute('pid', $task->get_pid());
      }
      else
      {
        $task_node->setAttribute('running', '0');
        $task_node->setAttribute('pid', '');
      }

    }

    $output = $ret->saveXML();


    break;
  case 'UNMOUNTBASE':
    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));
    $ret = array('sbas_id' => null);

    $databox = databox::get_instance((int) $parm['sbas_id']);
    $databox->unmount_databox($appbox);

    $ret['sbas_id'] = $parm['sbas_id'];
    $output = p4string::jsonencode($ret);
    break;

  case 'P_BAR_INFO':
    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));
    $ret = array(
        'sbas_id' => null,
        'indexable' => false,
        'records' => 0,
        'xml_indexed' => 0,
        'thesaurus_indexed' => 0,
        'viewname' => null,
        'printLogoURL' => NULL
    );

    $databox = databox::get_instance((int) $parm['sbas_id']);

    $ret['indexable'] = $appbox->is_databox_indexable($databox);
    $ret['viewname'] = (($databox->get_dbname() == $databox->get_viewname()) ? _('admin::base: aucun alias') : $databox->get_viewname());

    $ret['records'] = $databox->get_record_amount();

    $datas = $databox->get_indexed_record_amount();

    $ret['sbas_id'] = $parm['sbas_id'];
    $tot = $idxxml = $idxth = 0;

    $ret['xml_indexed'] = $datas['xml_indexed'];
    $ret['thesaurus_indexed'] = $datas['thesaurus_indexed'];

    if (file_exists($registry->get('GV_RootPath') . 'config/minilogos/logopdf_' . $parm['sbas_id'] . '.jpg'))
      $ret['printLogoURL'] = '/print/' . $parm['sbas_id'];
    $output = p4string::jsonencode($ret);
    break;

  case 'CHGVIEWNAME':

    $parm = $request->get_parms('sbas_id', 'viewname');
    $ret = array('sbas_id' => null, 'viewname' => null);
    $sbas_id = (int) $parm['sbas_id'];
    $databox = databox::get_instance($sbas_id);
    $appbox->set_databox_viewname($databox, $parm['viewname']);
    $output = p4string::jsonencode($ret);
    break;

  case 'MAKEINDEXABLE':

    $parm = $request->get_parms('sbas_id', 'INDEXABLE');
    $ret = array('sbas_id' => null, 'indexable' => null);
    $sbas_id = (int) $parm['sbas_id'];
    $databox = databox::get_instance($sbas_id);
    $appbox->set_databox_indexable($databox, $parm['INDEXABLE']);
    $ret['sbas_id'] = $parm['sbas_id'];
    $ret['indexable'] = $parm['INDEXABLE'];
    $output = p4string::jsonencode($ret);
    break;

  case 'REINDEX':

    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));
    $ret = array('sbas_id' => null);
    $sbas_id = (int) $parm['sbas_id'];
    $databox = databox::get_instance($sbas_id);
    $databox->reindex();
    $output = p4string::jsonencode($ret);
    break;

  case 'CLEARALLLOG':

    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));
    $ret = array('sbas_id' => null);
    $sbas_id = (int) $parm['sbas_id'];
    $databox = databox::get_instance($sbas_id);
    $databox->clear_logs();
    $ret['sbas_id'] = $parm['sbas_id'];
    $output = p4string::jsonencode($ret);
    break;

  case 'DELLOGOPDF':
    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));
    $ret = array('sbas_id' => null);
    $sbas_id = (int) $parm['sbas_id'];
    $databox = databox::get_instance($sbas_id);
    $appbox->write_databox_pic($databox, null, databox::PIC_PDF);
    $ret['sbas_id'] = $parm['sbas_id'];

    $output = p4string::jsonencode($ret);
    break;

  case 'DELETEBASE':
    $parm = $request->get_parms(array('sbas_id' => http_request::SANITIZE_NUMBER_INT));

    $ret = array('sbas_id' => null, 'err' => -1, 'errmsg' => null);

    try
    {
      $sbas_id = (int) $parm['sbas_id'];
      $databox = databox::get_instance($sbas_id);
      if ($databox->get_record_amount() == 0)
      {
        $databox->unmount_databox($appbox);
        $appbox->write_databox_pic($databox, null, databox::PIC_PDF);
        $databox->delete();
        $ret['sbas_id'] = $parm['sbas_id'];
        $ret['err'] = 0;
      }
      else
      {
        $ret['errmsg'] = _('admin::base: vider la base avant de la supprimer');
      }
    }
    catch (Exception $e)
    {
      $ret['errmsg'] = $e->getMessage();
    }
    $output = p4string::jsonencode($ret);
    break;
}
echo $output;
