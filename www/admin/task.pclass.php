<?php
class phrasea_task
{
	const LAUCHED_BY_BROWSER = 1;
	const LAUCHED_BY_COMMANDLINE = 2;
	
	public $launched_by = 0;
//	public $schedulerSocket = null;

	public $act = NULL;
	public $classname = NULL;
	public $taskid = NULL;
	public $conn = NULL;
	public $xmlSettins = NULL;
	
	public $system = '';		// "DARWIN", "WINDOWS" , "LINUX"...
	public $lng;
	
	
	public $argt = array(
					"--help" => array("set"=>false, "values"=>array(), "usage"=>" (no help available)")
		 		);
		 		
	function traceRam($msg='')
	{
		static $lastt=null;
		$t = explode(' ', ($ut=microtime()));
		if($lastt===null)
			$lastt = $t;
		$dt = ($t[0]-$lastt[0]) + ($t[1]-$lastt[1]);
		 
		$m = memory_get_usage()>>10;
		$d = debug_backtrace(false);

		$lastt = $t;
		 print($s);
	}
		 		
	function launchedBy()
	{
		return $this->launched_by;
	}

	function __construct()
	{
		$this->system = p4utils::getSystem();

		$this->launched_by = array_key_exists("REQUEST_URI", $_SERVER) ? self::LAUCHED_BY_BROWSER : self::LAUCHED_BY_COMMANDLINE;
		if($this->system != "DARWIN" && $this->system != "WINDOWS" && $this->system != "LINUX" )
		{
			if($this->launched_by == self::LAUCHED_BY_COMMANDLINE)
			{
				flush();
			}
			exit(-1);
		}
		else
		{
			if($this->launched_by == self::LAUCHED_BY_COMMANDLINE)
			{
				flush();
			}
		}
		
		$this->lng = GV_default_lng;
		$this->STRINGS = simplexml_load_string('<?xml version="1.0" encoding="ISO-8859-1" ?><strings/>');
	}
	
	public function getUsage()
	{
		global $argc, $argv;
		$t = "usage: ".$argv[0]." [options]\noptions:\n";
		foreach($this->argt as $n=>$v)
			$t .= "\t". $n . $v["usage"] . "\n";
		return($t);
	}
	
	public function getName()
	{
		return("not named task");
	}
	
	function setProgress($done, $todo)
	{
		if($todo > 0)
			$p = (int)((100 * $done) / $todo);
		else
			$p = -1;
			
		$conn = connection::getInstance();
		$sql = 'UPDATE task2 SET completed=\''.$p.'\' WHERE task_id=' . $this->taskid;
		$conn->query($sql);
	}
}
