<?php
define("PARSED", true);
define("UNPARSED", false);

$endCharacters_iso = "\t\r\n !\"#\$%&'()+,-./:;<=>@[\]^_`{|}~£§¨°";
$endCharacters_utf8 = utf8_encode($endCharacters_iso);

$whiteCharacters_iso = " \t\n\r\0\x0B";
$whiteCharacters_utf8 = utf8_encode($whiteCharacters_iso);

function noaccent_utf8($s, $parsed=UNPARSED)
{
  global $t_LowNoDiacritics, $endCharacters_utf8;
	$so = "";
	$l=mb_strlen($s, "UTF-8");
	$lastwasblank = false;
	for($i=0; $i<$l; $i++)
	{
		$c = mb_substr($s, $i, 1, "UTF-8");
		$c = isset($t_LowNoDiacritics["cmap"][$c]) ? $t_LowNoDiacritics["cmap"][$c] : $c;
		if($parsed)
		{
			if(mb_strpos($endCharacters_utf8, $c)!==FALSE)
			{
				$lastwasblank = true;
			}
			else
			{
				if($lastwasblank && $so!="")
					$so .= " ";
				$so .= $c;
				$lastwasblank = false;
			}
		}
		else
		{
			$so .= $c;
		}
	}
	return($so);
}
?>
