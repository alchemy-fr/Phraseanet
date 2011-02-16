<?php
function print_usage(&$argt)
{
	global $argc, $argv;
	printf("usage: %s [options]\noptions:\n", $argv[0]);
	foreach($argt as $n=>$v)
		printf("\t%s%s\n", $n, $v["usage"]);
}
function parse_cmdargs(&$argt, &$err)
{
	$err = "";
	global $argc, $argv;
	
	for($a=1; $a<$argc; $a++)
	{
		//echo "parse_cmdargs :: $a\n";
		
		$arg = $argv[$a];


		if($arg=="--" || $arg=="-")
			continue;
		if(($p = strpos($arg, "=")) === false)
		{
			parse_arg($arg, $argt, $err);
		}
		else
		{
			parse_arg(substr($arg, 0, $p), $argt, $err);	
			parse_arg("=", $argt, $err);	
			parse_arg(substr($arg, $p+1), $argt, $err);	
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
	return($err == "");
}
function parse_arg($arg, &$argt, &$err)
{
	static $last_arg="";
	static $curopt = null;
	
	if($arg != "=")
	{
		if($last_arg != "=")
		{
			if(isset($argt[$arg]))
				$argt[$curopt = $arg]["set"] = true;
			else
			{
				$err .= "option '" . $arg . "' inconnue.\n";
				if(isset($argt["--help"]))
					$argt["--help"]["set"] = true;
			}
		}
		else
		{
			if($curopt)
				$argt[$curopt]["values"][] = $arg;
			else
			{
				$err .= "'=' doit suivre un nom d'option.\n";
				if(isset($argt["--help"]))
					$argt["--help"]["set"] = true;
			}
		}
	}
	$last_arg = $arg;
}
?>
