<?php


class task_test extends phraseatask
{
	// ======================================================================================================
	// ===== les interfaces de settings (task2.php) pour ce type de t�che
	// ======================================================================================================

	// ====================================================================
	// getName() : must return the name of this kind of task (utf8), MANDATORY
	// ====================================================================
	public function getName()
	{
		return(utf8_encode("Test"));
	}
	
	// ====================================================================
	// graphic2xml : must return the xml (text) version of the form
	// ====================================================================
	/*
	public function graphic2xml($oldxml)
	{
		return($oldxml);
	}
	*/
	
	// ====================================================================
	// xml2graphic : must fill the graphic form (using js) from xml
	// ====================================================================
	/*
	public function xml2graphic($xml, $form)
	{
		return(true);
	}
	*/
	
	// ====================================================================
	// printInterfaceJS() : print the js part of the graphic view
	// ====================================================================
	/*
	public function printInterfaceJS()
	{
	}
	*/
	
	// ====================================================================
	// printInterfaceHTML(..) : print the html part of the graphic view
	// ====================================================================
	/*
	public function printInterfaceHTML()
	{
?>
		<form name="graphicForm" onsubmit="return(false);" method="post">
		</form>
<?php
	}
	*/
	
	// ====================================================================
	// getGraphicForm() : must return the name graphic form to submit
	// if not implemented, assume 'graphicForm'
	// ====================================================================
	/*
	public function getGraphicForm()
	{
		return('graphicForm');
	}
	*/
	
	
	
	// ====================================================================
	// $argt : command line args specifics to this task
	// ====================================================================
	/*
	public $argt = array(
		 			);
	*/

	// ======================================================================================================
	// help() : text displayed if --help
	// ======================================================================================================
	public function help()
	{
		return(utf8_encode("just saying hello each second"));
	}
		 			

	
	
	
	// ======================================================================================================
	// run() : the real code executed by each task, MANDATORY
	// ======================================================================================================

	function run()
	{
		printf("taskid %s starting.".PHP_EOL, $this->taskid);
		// task can't be stopped here
		sleep(6);
		
		$conn = connection::getInstance();
		$running = true;
		while($conn && $running)
		{
			printf("hello world I'm task %s".PHP_EOL, $this->taskid);
			flush();
			
			$sql = "SELECT status FROM task2 WHERE status='tostop' AND task_id=" . $this->taskid ;
			if($rs = $conn->query($sql))
			{
				if($row = $conn->fetch_assoc($rs))
				{
					// simulate bug : never ending task
					$running = false;
				}
				$conn->free_result($rs);
			}
			$conn->close();
			unset($conn);
			sleep(5);
			$conn = connection::getInstance();
		}
		printf("taskid %s ending.".PHP_EOL, $this->taskid);
		
		// task can't be (re)started here	
		sleep(1);
		
		printf("good bye world I was task %s".PHP_EOL, $this->taskid);
		
		flush();
		return 'stopped';
	}
	
	
}

?>