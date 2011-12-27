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
require_once __DIR__ . "/../../lib/bootstrap.php";

$appbox = appbox::get_instance();
$session = $appbox->get_session();
$registry = $appbox->get_registry();
$request = http_request::getInstance();
$parm = $request->get_parms('key');

$scheduler_key = phrasea::scheduler_key();

$good_user = false;
if ($session->is_authenticated())
{
  $user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
  if ($user->ACL()->has_right('taskmanager'))
    $good_user = true;
}

if (!$good_user && (trim($scheduler_key) == '' || $scheduler_key !== $parm['key']))
{
  phrasea::headers(403);
}

set_time_limit(0);
session_write_close();
ignore_user_abort(true);


$system = system_server::get_platform();
if ($system != "DARWIN" && $system != "WINDOWS" && $system != "LINUX")
{
  phrasea::headers(500);
}
$logdir = p4string::addEndSlash($registry->get('GV_RootPath') . 'logs');

$phpcli = $registry->get('GV_cli');

switch ($system)
{
  case "DARWIN":
    $cmd = $phpcli . ' -f ' . $registry->get('GV_RootPath') . "bin/console scheduler:start";
    break;
  case "LINUX":
    $cmd = $phpcli . ' -f ' . $registry->get('GV_RootPath') . "bin/console scheduler:start";
    break;
  case "WINDOWS":
  case "WINDOWS NT":
    $cmd = $phpcli . ' -f ' . $registry->get('GV_RootPath') . "bin/console scheduler:start";
    break;
}


if ($logdir)
{
  $descriptors[1] = array("file", $logdir . "scheduler.log", "a+");
  $descriptors[2] = array("file", $logdir . "scheduler.error.log", "a+");
}
else
{
  $descriptors[1] = array("file", "NUL", "a+");
  $descriptors[2] = array("file", "NUL", "a+");
}

$pipes = null;
$cwd = $registry->get('GV_RootPath') . "bin/";
$proc = proc_open($cmd, $descriptors, $pipes, $cwd, null, array('bypass_shell' => true));

$pid = NULL;
if (is_resource($proc))
{
  $proc_status = proc_get_status($proc);
  if ($proc_status['running'])
    $pid = $proc_status['pid'];
}
if ($pid !== NULL)
{
  $msg = sprintf("scheduler '%s' started (pid=%s)", $cmd, $pid);
  my_syslog(LOG_INFO, $msg);
}
else
{
  @fclose($pipes[1]);
  @fclose($pipes[2]);
  @proc_close($process);

  $msg = sprintf("scheduler '%s' failed to start", $cmd);
  my_syslog(LOG_INFO, $msg);
}

function my_syslog($level, $msg)
{
  print($msg . "\n");
}
