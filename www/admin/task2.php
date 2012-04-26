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
$parm = $request->get_parms('act'  // NEWTASK or SAVETASK
    , "tid"
    , 'tcl' // task class
    , 'view' // XML ou GRAPHIC
);

$lng = Session_Handler::get_locale();
$task_manager = new task_manager($appbox);

phrasea::headers();

if ( ! $parm['view'])
    $parm['view'] = 'GRAPHIC';


$refreshfinder = false;
$out = "";

try {
    switch ($parm['act']) {
        case 'NEWTASK':  // blank task from scratch, NOT saved into sql
            $task = task_abstract::create($appbox, $parm['tcl']);
            break;
        case 'EDITTASK': // existing task
            $task = $task_manager->get_task($parm['tid']);
            break;
        default:
            throw new Exception('Unknown action');
            break;
    }
} catch (Exception $e) {
    phrasea::headers(404);
}

$zGraphicForm = 'graphicForm';
$hasGraphicMode = false;

if (method_exists($task, 'getGraphicForm')) {
    $hasGraphicMode = true;
    $zGraphicForm = $task->getGraphicForm();
} else {
    $parm['view'] = 'XML';
}

function stripdoublequotes($value)
{
    return str_replace(array("\r\n", "\r", "\n", "\""), array('', '', '', '\"'), $value);
}
if ( ! $task->getGraphicForm()) {
    $parm['view'] = 'XML';
}

$core = \bootstrap::getCore();
$twig = $core->getTwig();

if ( ! $task->getGraphicForm()) {
    $parm['view'] = 'XML';
}

echo $twig->render('admin/task.html', array('task' => $task, 'view' => $parm['view']));
