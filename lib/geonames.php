<?php

function findGeoname($cityName)
{	
	$output = '';
	
	$bg = '';
	if(strlen(trim($cityName))>=1)
	{
		$url = 'http://localization.webservice.alchemyasp.com/find_city.php?city='.urlencode($cityName).'&maxResult=30';
		
		$xml = p4::getUrl($url);
		if($xml)
		{
			$sxe = simplexml_load_string($xml);
			if($sxe)
				foreach($sxe->geoname as $geoname)
				{
					$bg = $bg=='boxI'?'boxP':'boxI';
					
					$f = mb_strlen((string)$geoname->title_match);
					$geoname->title = '<span style="background-color:black;">'.mb_substr($geoname->title,0,$f).'</span>'.mb_substr($geoname->title,$f);
					 
					if((string)$geoname->country_match != '')
					{
							$f = mb_strlen((string)$geoname->country_match);
							$geoname->country = '<span style="background-color:black;">'.mb_substr($geoname->country,0,$f).'</span>'.mb_substr($geoname->country,$f);
					}
					
					$output .= "<div class='box ".$bg."' id='geo_".(string)$geoname->geonameid."'>
						<div>".$geoname->title.", ".$geoname->country."</div>
						".(trim($geoname->region)!=''?"<div>".$geoname->region.", ".$geoname->country."</div>":'')."
					</div>";
					
				}
		}
		if($output == '')
			$output = "<div class='box boxI unselectable'>	<div>No matches found</div></div>";
	}
	else
				$output = "<div class='box boxI unselectable'>	<div>Type to select a city/town</div></div>";
	
	return $output;
}


?>