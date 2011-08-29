<?php
require_once dirname( __FILE__ ) . "/../lib/bootstrap_task.php";
phrasea::start();					// because it's not done in bootstrap_task.php anymore to low the number of actives cnx
$session = session::getInstance();	// because it's not done in bootstrap_task.php anymore to low the number of actives cnx

$argt = array(
				"--help" =>           array("set"=>false, "values"=>array(), "usage"=>"[={task_id|classname}] : this help | help of task {task_id} | help of {classname}"),
				"--display_errors" => array("set"=>false, "values"=>array(), "usage"=>"             : force php ini_set('display_errors', 1)"),
				"--taskid" =>         array("set"=>false, "values"=>array(), "usage"=>"={id}                : id of task to launch")
);


$system = p4utils::getSystem();

if($system != "DARWIN" && $system != "WINDOWS" && $system != "LINUX" )
{
	printf(("runtask::sorry, this program does not run under '%s'\n"), $system);
	flush();
	exit(-1);
}
else
{
//	printf("Bonjour, votre OS est '" . $system . "'.\n");
	flush();
}

$RETURN = $system=="WINDOWS" ? "\r\n" : "\n";

// on commence par parser les arguments propres a 'runstask' : taskid, help
if(!parse_cmdargs($argt, $err, false))
{
	print($err);
	getUsage($argt, true);
	flush();
	exit(-1);
}

if($argt["--display_errors"]["set"])
{
	ini_set('display_errors', 1);
}

if($argt["--help"]["set"])
{
	if(isset($argt["--help"]["values"][0]))
	{
		$harg = $argt["--help"]["values"][0];
		if((int)$harg > 0)
		{
			$conn = connection::getInstance();
			$sql = "SELECT * FROM task2 WHERE task_id='".$conn->escape_string($harg)."'";
			$rowtask = null;
			if($rs = $conn->query($sql))
			{
				$rowtask = $conn->fetch_assoc($rs);
				$conn->free_result($rs);
			}	
			if($rowtask)
			{
				$classname = $rowtask['class'];
			}
			else
			{
				printf(("runtask::ERROR : task id %s not found\n"), $taskid);
				exit(-1);
			}
		}
		else
		{
			// on a fait '--help=azerty' : on demande l'aide de la classe 'azerty'
			$classname = $harg;
		}
			
		$ztask = null;
//		if($classname && file_exists("./tasks/$classname.class.php"))
//		{
//			require("./tasks/$classname.class.php");
//			if(class_exists($classname))
//			{
				$ztask = new $classname();
//			}
//			if(!$ztask)
//			{
//				printf("ERROR : file '$classname.class.php' is not a proper task class\n");
//				exit(-1);
//			}
//		}
//		else
//		{
//			printf("ERROR : error loading file '$classname.class.php'\n");
//			exit(-1);
//		}
		// on parse maintenant les arguments propres a cette classe de tache
		// on rajoute les args de la classe aux args par defaut
		$ztask->argt = array_merge($argt, $ztask->argt);
		if(!parse_cmdargs($ztask->argt, $err, true)  || $ztask->argt["--help"]["set"])
		{
			print($err);
			getUsage($ztask->argt, false);
			if(method_exists($ztask, 'help'))
			{
				$t = str_replace("\n", "\n\t", utf8_decode($ztask->help()));
				printf(("runtask::about:\n\t%s\n"), $t);
			}
			flush();
			exit(-1);
		}
	}
	else
	{
		// on a fait '--help' tout court
		getUsage($argt, true);
		flush();
		exit(-1);
	}
	exit(-1);
}


if(!$argt["--taskid"]["set"])
{
	// si on n'a pas fait '--help' alors '--taskid=xx' est obligatoire 
	getUsage($argt, true);
	flush();
	printf(("runtask::ERROR : option 'taskid' not set\n"));
	exit(-1);
}


$classname = null;
$taskid = $argt["--taskid"]["values"][0];


$taskSettings = null;

$conn = connection::getInstance();


$sql = "SELECT * FROM task2 WHERE task_id='".$conn->escape_string($taskid)."'";
$rowtask = null;
if($rs = $conn->query($sql))
{
	$rowtask = $conn->fetch_assoc($rs);
	$conn->free_result($rs);
}


if($rowtask)
{
//	if($rowtask['status'] != 'stopped')
//	{
//		printf("task id $taskid not in 'stopped' state\n");
//		exit(-1);
//	}
	$classname = $rowtask["class"];
	$taskSettings = $rowtask["settings"];
}
else
{
	printf(("runtask::ERROR : task id %s not found\n"), $taskid);
	exit(-1);
}



// try to lock one instance of this task

p4::fullmkdir($lockdir = GV_RootPath.'tmp/locks/');

$tasklock = fopen(($lockfile = ($lockdir . 'task_'.$taskid.'.lock')), 'a+');
if(flock($tasklock, LOCK_EX|LOCK_NB ) != true)
{
	printf(("runtask::ERROR : task already running.\n"), $taskid);
	fclose($tasklock);
	exit(-1);
}
else 
{
	ftruncate($tasklock, 0);
	fwrite($tasklock, ''.getmypid());
	fflush($tasklock);
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
//		printf("ERROR : file '$classname.class.php' is not a proper task class\n");
//		fclose($tasklock);
//		@unlink($lockfile);
//		exit(-1);
//	}
//}
//else
//{
//	printf("ERROR : error loading file '$classname.class.php'\n");
//	fclose($tasklock);
//	@unlink($lockfile);
//	exit(-1);
//}

// on parse maintenant les arguments propres � cette classe de t�che
// on rajoute les args de la classe aux args par d�faut
$ztask->argt = array_merge($argt, $ztask->argt);
// printf("class $classname loaded\n");
if(!parse_cmdargs($ztask->argt, $err, true) ) //  || $argt["--help"]["set"])
{
	print($err);
	// print("parsing argt\n");
//	print(getUsage($argt, false));
	// printf("parsing ztask->argt (%s)\n", var_export($ztask->argt, true));
	print(getUsage($ztask->argt, false));
	flush();
	die;
}


// ici normalement la tache va tourner
$sql = "UPDATE task2 SET status='manual', pid='".$conn->escape_string(getmypid())."' WHERE task_id='".$conn->escape_string($taskid)."'";
$conn->query($sql);

// on �x�cute la tache
$ztask->taskid = $taskid;
$ztask->classname = $classname;
$ztask->taskSettings = $taskSettings;


printf(("runtask::%s : '%s' (taskid=%s) started.\n"), date("r"), $ztask->getName(), $ztask->taskid);

$ret_status = $ztask->run();

printf(("runtask::%s : '%s' (taskid=%s) ended.\n"), date("r"), $ztask->getName(), $ztask->taskid);


// la tache est finie
if($ret_status)
{
	$sql = "UPDATE task2 SET status='".$conn->escape_string($ret_status)."', pid=0 WHERE task_id='".$conn->escape_string($taskid)."'";
	$conn->query($sql);
}



//file_put_contents($tasklock, '');
flock($tasklock, LOCK_UN|LOCK_NB );
ftruncate($tasklock, 0);
fclose($tasklock);
@unlink($lockfile);


// --------------------------------------------------------------------------------------------------------

function getUsage(&$argt, $listtask)
{
	global $argc, $argv;
	$t = "";
	$t .= sprintf(("runtask::usage: %s [options]\noptions:\n"), $argv[0]);
	foreach($argt as $n=>$v)
		if($v["usage"])
			$t .= "\t". $n . $v["usage"] . "\n";
	print($t);
	
	if($listtask)
	{
		$conn = connection::getInstance();
		if($conn)
		{
			$ttask = array();
			$twidth = array();
			$sql = "SELECT * FROM task2";
			if($rs = $conn->query($sql))
			{
				while($row = $conn->fetch_assoc($rs))
				{
					$ttask[] = $row;
					foreach($row as $f=>$v)
					{
						if(!isset($twidth[$f]) || strlen($v) > $twidth[$f])
							$twidth[$f] = strlen($v);
					}
				}
				$conn->free_result($rs);
				foreach($twidth as $k=>$v)
				{
					if(strlen($k) > $v)
						$twidth[$k] = strlen($k);
				}
				
				$sep = $fmt = "|\n";
				foreach(array('task_id', 'name', 'status') as $f)
				{
					$sep = "|-".str_repeat('-', $twidth[$f])."-" . $sep;
					$fmt = "| %".$twidth[$f]."s " . $fmt;
				}
				print($sep);
				printf($fmt, 'task_id', 'name', 'status');
				print($sep);
				foreach($ttask as $task)
					printf($fmt, $task['task_id'], $task['name'], $task['status']);
				print($sep);
			}
		}
	}
}

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
				$err .= sprintf(("runtask::unknown option '%s'.\n"), $arg);
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
				$err .= ("runtask::'=' must follow an option's name.\n");
//				if(isset($argt["--help"]))
//					$argt["--help"]["set"] = true;
			}
		}
	}
	$last_arg = $arg;
}


?>