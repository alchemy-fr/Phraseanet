<?php

class format {

	static public function arr_to_csv_line($arr, $tri_column = false)
	{
		$line = array();
		$tmp = array();
 		foreach ($arr as $v)
 		{
 			if(is_array($v))
 			{
 				$line[] = self::arr_to_csv_line($v);
 			}
 			elseif($tri_column)
 			{	
 				
 				$key = array_search($v, $arr);
 				unset($arr[$key]);
 				
	 			if(array_key_exists($key, $tri_column))
	 			{
	 				$tmp[$key] = $v;
	 			}
 			}
 			else
 				$line[] = '"' . str_replace('"', '""', strip_tags($v)) . '"';
      	}
      	if($tri_column)
      	{
      		foreach($tri_column as $key => $value)
      		{
      			foreach($tmp as $k => $v)
      			{
      				if($key == $k)
      				{
      					$line[] = '"' . str_replace('"', '""', strip_tags($v)) . '"';
      				}
      			}
      		}
      	}
      	
      	return implode(",", $line);
	}
   
	static public function arr_to_csv($arr, $tri_column = false)
	{
		$lines = array();
		
		if($tri_column)
		{
			$title ="";
			foreach($tri_column as $k => $v)
	 		{
	 			if(isset($v['title']))
	 				$title .= (empty($title) ? "" : ",") . '"' . str_replace('"', '""', strip_tags($v['title'])) . '"';
	 		}
	 		!empty($title) ? $lines[] = $title : "";
		}
		foreach ($arr as $v) 
		{
			$lines[] = self::arr_to_csv_line($v, $tri_column);
		}
		return implode("\n", $lines);
	}
	
	static public function wrap_arr_el($arr, $left_str, $right_str)
    {
    	if( !is_array($arr) && !is_string($left_str) && !is_string($right_str))
    		return false;
    	
    	return "$left_str".implode("$right_str $left_str", $arr)."$right_str";
    }
    
	static public function arr_to_table_cells($arr)
	{
		$line = array();
		print_r($arr);
 		foreach ($arr as $v)
 		{
      		$line[] = is_array($v) ? self::arr_to_table_cell($v) : $v;
      	}
      	return self::wrap_arr_el($line, "<td>", "</td>");
	}
	
	static public function arr_to_table_rows($arr)
	{
		$lines = array();
		foreach ($arr as $v) 
		{
			$lines[] = self::arr_to_table_cells($v);
		}
		return self::wrap_arr_el($line, "<tr>", "</tr>");
	}
	
	static public function my_printr($data, $file = false , $line = false)
	{
		if($file)
			echo "file : $file <br />";
		if($line)
			echo "line : $line <br />";
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
	
	static public function my_vdump($data, $file = false , $line = false)
	{
		if($file)
			echo "file : $file <br />";
		if($line)
			echo "line : $line <br />";
		echo "<pre>";
		var_dump($data);
		echo "</pre>";
	}
	//beurk
	static public function my_format_printr($data, $file = false, $line = false)
	{
		if($file)
			echo "file : $file <br />";
		if($line)
			echo "line : $line <br />";
		
		$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($data));
		foreach($it as $key => $value) 
		{
			$d = $it->getDepth();
			$i = 0;
		    while($i < $d)
		    {
		    	echo "&nbsp;&nbsp;";
		    	$i++;
		    }
		    echo "[<b>$key</b>] => $value <br />";
		}
	}
	
	static public function arr_to_xml($arr, $DOM = null, $root = null, $rootname = 'array', $name = 'row')
	{
	    if($DOM  == null)
	    {
			$DOM  = new DOMDocument('1.0', 'UTF-8');
			$DOM->formatOutput = true;
			$DOM->preserveWhiteSpace = false;
	    }
	    
		if($root == null)
		{
			$root = $DOM->appendChild($DOM->createElement($rootname));
		}
		
		foreach($arr as $key => $value)
		{   
			if(is_int($key) && $name != null)
			{
				if(is_array($value))
				{
					$subroot = $root->appendChild($DOM->createElement($name));
					self::arr_to_xml($value, $DOM, $subroot);
				}
				elseif(is_scalar($value))
				{
					$node = $root->appendChild($DOM->createElement($name));
					$node->appendChild($DOM->createTextNode($value));
				}
			}
			elseif(is_string($key) && $key != $name)
			{
				if(is_array($value))
				{
					$subroot = $root->appendChild($DOM->createElement($key));
					self::arr_to_xml($value, $DOM, $subroot);
				}
				elseif(is_scalar($value))
				{
					$node = $root->appendChild($DOM->createElement($key));
					$node->appendChild($DOM->createTextNode($value));
				}
			}
		}
	}
	

}