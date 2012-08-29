<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);

$request = http_request::getInstance();
$registry = $appbox->get_registry();
$parm = $request->get_parms('action', 'position', 'test', 'renew', 'path', 'tests');

$output = '';

switch ($parm['action']) {
    case 'APACHE':
        if ($parm['test'] == 'success') {
            $output = '1';
        } else {
            $output = '0';
        }
        break;
    case 'SCHEDULERKEY':
        $output = $registry->get('GV_ServerName') . 'admin/runscheduler.php?key=' . urlencode(phrasea::scheduler_key( ! ! $parm['renew']));
        break;

    case 'SETTASKSTATUS':
        $parm = $request->get_parms('task_id', 'status', 'signal');
        try {
            $task_manager = new task_manager($appbox);
            $task = $task_manager->getTask($parm['task_id']);
            $pid = (int) ($task->getPID());
            $task->setState($parm["status"]);
            $signal = (int) ($parm['signal']);
            if ($signal > 0 && $pid) {
                posix_kill($pid, $signal);
            }
        } catch (Exception $e) {

        }
        $output = json_encode($pid);
        break;

    case 'SETSCHEDSTATUS':
        $parm = $request->get_parms('status', 'signal');
        try {
            $task_manager = new task_manager($appbox);

            $task_manager->setSchedulerState($parm['status']);
        } catch (Exception $e) {

        }
        break;

    case 'RESETTASKCRASHCOUNTER':
        $parm = $request->get_parms("task_id");
        try {
            $task_manager = new task_manager($appbox);
            $task = $task_manager->getTask($parm['task_id']);
            $task->resetCrashCounter();
        } catch (Exception $e) {

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

        try {
            $task_manager = new task_manager($appbox);
            $task = $task_manager->getTask($parm["task_id"]);
            /**
             * @todo checker, cette methode n'est pas implementee partout
             */
            $root->setAttribute("crashed", $task->getCrashCounter());
            if ($task->saveChanges($conn, $parm["task_id"], $row)) {
                $root->setAttribute("saved", "1");
            }
        } catch (Exception $e) {

        }

        $output = $ret->saveXML();
        break;
    case 'PINGSCHEDULER_JS':
        $parm = $request->get_parms('dbps');
        $ret = array('time' => date("H:i:s"));

        $task_manager = new task_manager($appbox);
        $ret['scheduler'] = $task_manager->getSchedulerState();

        $ret['tasks'] = array();

        foreach ($task_manager->getTasks(true) as $task) {
            if ($task->getState() == task_abstract::STATE_TOSTOP && $task->getPID() === NULL) {
                // fix
                $task->setState(task_abstract::STATE_STOPPED);
            }
            $id = $task->getID();
            $ret['tasks'][$id] = array(
                'id'        => $id
                , 'pid'       => $task->getPID()
                , 'crashed'   => $task->getCrashCounter()
                , 'completed' => $task->getCompletedPercentage()
                , 'status'    => $task->getState()
            );
        }

        if ($parm['dbps']) {
            $sql = 'SHOW PROCESSLIST';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetchALL(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            $ret['db_processlist'] = array();
            foreach ($rows as $row) {
                if ($row['Info'] != $sql) {
                    $ret['db_processlist'][] = $row;
                }
            }
        }

        $output = p4string::jsonencode($ret);
        break;

}

unset($appbox);
echo $output;
