<?php
require_once dirname( __FILE__ ) . "/../lib/bootstrap_task.php";
require_once(GV_RootPath."lib/index_utils2.php");

$argt = array(
				"--taskid" => array("set"=>false, "values"=>array(), "usage"=>"={id}        : id of task to launch")
			);

// on commence par parser l'argument propre � 'schedtask' : taskid
if(!parse_cmdargs($argt, $err, false) || !$argt["--taskid"]["set"])
{
	// --taskid=xx' est obligatoire 
	printf("option 'taskid' not set\n");
	exit(-1);
}

$classname = null;
$taskid = $argt["--taskid"]["values"][0];

$taskSettings = null;

$conn = connection::getInstance();


$sql = "SELECT * FROM task2 WHERE task_id='".$conn->escape_string($taskid)."'";
if($rs = $conn->query($sql))
{
	if($row = $conn->fetch_assoc($rs))
	{
		$classname = $row["class"];
		$taskSettings = $row["settings"];
	}
	else
	{
		printf("task id $taskid not found\n");
		exit(-1);
	}
	$conn->free_result($rs);
}


// try to lock one instance of this task

p4::fullmkdir($lockdir = GV_RootPath.'tmp/locks/');

for($try=10; $try>=0; $try--)
{
	if( ($tasklock = fopen(($lockfile = ($lockdir . 'task_'.$taskid.'.lock')), 'w')) )
	{
		if(flock($tasklock, LOCK_EX|LOCK_NB) != true)
		{
			printf("failed to lock '%s' (try=%s) \n", $lockfile, $try);
			if($try == 0)
			{
				printf("task %s already running.\n", $taskid);
				fclose($tasklock);
				exit(-1);
			}
			else
			{
				sleep(2);
			}
		}
		else 
		{
			ftruncate($tasklock, 0);
			fwrite($tasklock, ''.getmypid());
			fflush($tasklock);
			break;
		}
	}
	else
	{
		printf("failed to fopen '%s' \n", $lockfile);
	}
}



$ztask = null;
//if($classname && file_exists("./tasks/$classname.class.php"))
//{
//	require("./tasks/$classname.class.php");
//	if(class_exists($classname))
//	{
		$ztask = new $classname($taskid);
//	}
//	if(!$ztask)
//	{
//		printf("file '$classname.class.php' is not a proper task class\n");
//		fclose($tasklock);
//		@unlink($lockfile);
//		exit(-1);
//	}
//}
//else
//{
//	printf("error loading file '$classname.class.php'\n");
//	fclose($tasklock);
//	@unlink($lockfile);
//	exit(-1);
//}


// ici normalement la tache va tourner
$sql = "UPDATE task2 SET status='started', pid='".$conn->escape_string(getmypid())."' WHERE task_id='".$conn->escape_string($taskid)."'";
$conn->query($sql);


// on �x�cute la tache
$ztask->taskid = $taskid;
$ztask->classname = $classname;
$ztask->taskSettings = $taskSettings;

$ztask->log(sprintf('%s (taskid=%s) started.', $ztask->getName(), $ztask->taskid));

$ret_status = $ztask->run();

unset($conn);
$conn = connection::getInstance();

$ztask->log(sprintf('%s (taskid=%s) ended returning(\'%s\').', $ztask->getName(), $ztask->taskid, $ret_status));

// la tache est finie
if($ret_status)	// task asked to change her status
{
	if($conn->ping())
	{
		
		if($ret_status == 'todelete')
		{
			$sql = "DELETE FROM task2 WHERE task_id='".$conn->escape_string($taskid)."'";
			$conn->query($sql);
		}
		else
		{
			$sql = "UPDATE task2 SET status='".$conn->escape_string($ret_status)."', pid=0 WHERE status != 'tostop' AND task_id='".$conn->escape_string($taskid)."'";
			$conn->query($sql);
			$sql = "UPDATE task2 SET status='stopped', pid=0 WHERE status = 'tostop' AND task_id='".$conn->escape_string($taskid)."'";
			$conn->query($sql);
		}
	}
	else
	{
		// task must have died because a lost of cnx !
		// since we can't change status also, we must grant the scheduler to restart us
		// nb : when sheduler gains cnx again, it will restart task where status='running' since it thinks they just crashed
		// BUT : in this case (cnx lost/gain), it will reset the crash counter since task did no realy 'crashed'
	}
}


// file_put_contents($tasklock, '');
ftruncate($tasklock, 0);
fclose($tasklock);
//flock($tasklock, LOCK_UN|LOCK_NB );
/*
for($try=1; $try<10; $try++)
{
	if($r = unlink($lockfile))
	{
		// printf("unlink %s (try=%s) returned %s \n", $lockfile, $try, $r+0);
		break;
	}
	else
	{
		// printf("failed to unlink %s (try=%s) returned %s \n", $lockfile, $try, $r+0);
		sleep(2);
	}
}
// printf("file_exists(%s) = %s \n", $lockfile, 0+file_exists($lockfile));
*/
// --------------------------------------------------------------------------------------------------------

function parse_cmdargs(&$argt, &$err, $printerrors=true)
{
	$err = "";
	global $argc, $argv;
	
	for($a=1; $a<$argc; $a++)
	{
		$arg = $argv[$a];
		if($arg=="--" || $arg=="-")
			continue;
		if(($p = strpos($arg, "=")) === false)
		{
			parse_arg($arg, $argt, $err, $printerrors);
		}
		else
		{
			parse_arg(substr($arg, 0, $p), $argt, $err, $printerrors);	
			parse_arg("=", $argt, $err, $printerrors);	
			parse_arg(substr($arg, $p+1), $argt, $err, $printerrors);	
		}
	}
	foreach($argt as $n=>$v)
	{
		if(!isset($v["values"][0]) && isset($v["default"]))
		{
			$argt[$n]["set"] = true;
			$argt[$n]["values"][] = $v["default"];
		}
	}
//	printf("end parse_cmdargs(..) ! \n");
//	var_dump($argt);
	return($err == "");
}

function parse_arg($arg, &$argt, &$err, $printerrors)
{
	static $last_arg="";
	static $curopt = null;
	if($arg != "=")
	{
		if($last_arg != "=")
		{
			if(isset($argt[$arg]))
				$argt[$curopt = $arg]["set"] = true;
			elseif($printerrors)
			{
				$err .= "option '" . $arg . "' inconnue.\n";
//				if(isset($argt["--help"]))
//					$argt["--help"]["set"] = true;
			}
		}
		else
		{
			if($curopt)
				$argt[$curopt]["values"][] = $arg;
			elseif($printerrors)
			{
				$err .= "'=' doit suivre un nom d'option.\n";
//				if(isset($argt["--help"]))
//					$argt["--help"]["set"] = true;
			}
		}
	}
	$last_arg = $arg;
}


?>