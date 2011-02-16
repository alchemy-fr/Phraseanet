<?php
function noaccent_utf8($s)
{
  global $t_LowNoDiacritics;
	$so = "";
	$l=mb_strlen($s, "UTF-8");
	for($i=0; $i<$l; $i++)
	{
		$c = mb_substr($s, $i, 1, "UTF-8");
		$so .= isset($t_LowNoDiacritics["cmap"][$c]) ? $t_LowNoDiacritics["cmap"][$c] : $c;
	}
	return($so);
}
?>