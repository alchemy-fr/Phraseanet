<?php
require_once dirname( __FILE__ ) . "/../../lib/bootstrap.php";

$session = session::getInstance();
$request = httpRequest::getInstance();
$parm = $request->get_parms('key');

$scheduler_key = phrasea::scheduler_key();

$good_user = false;
if($session->is_authenticated())
{
  $user = user::getInstance($session->usr_id);
  if($user->_global_rights['taskmanager'] == true)
    $good_user = true;
}

if(!$good_user && (trim($scheduler_key) == '' || $scheduler_key !== $parm['key']))
{
  phrasea::headers(403);
}

set_time_limit(0);
session_write_close();
ignore_user_abort(true);

$system = p4utils::getSystem();
if($system != "DARWIN" && $system != "WINDOWS" && $system != "LINUX" )
{
	phrasea::headers(500);
}

$logdir = p4string::addEndSlash(GV_RootPath.'logs');

$phpcli = GV_cli;

// cette tache est nouvelle
switch($system)
{
	case "DARWIN":
		$cmd = $phpcli . ' -f ' . "scheduler.exe.php";
		break;
	case "LINUX":
	 	$cmd = $phpcli . ' -f ' . GV_RootPath."bin/scheduler.exe.php";
		break;
	case "WINDOWS":
	case "WINDOWS NT":
		$cmd = $phpcli . ' -f ' . "scheduler.exe.php";
		break;
}


if($logdir)
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
$cwd = GV_RootPath . "bin/";
$proc = proc_open($cmd, $descriptors, $pipes, $cwd, null, array('bypass_shell'=>true) );
							
$pid = NULL;
if(is_resource($proc))
{
	$proc_status = proc_get_status($proc);
	if($proc_status['running'])
		$pid = $proc_status['pid'];
}
if($pid !== NULL)
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


