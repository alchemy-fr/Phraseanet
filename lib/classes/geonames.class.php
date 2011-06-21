<?php
class geonames
{
	
	public static function name_from_id($geonameid)
	{
		
		$url = 'http://localization.webservice.alchemyasp.com/get_name.php?geonameid='.$geonameid;
		
		$ret = '';
		
		$xml = p4::getUrl($url);
		if($xml)
		{
		
			$sxe = simplexml_load_string($xml);
			
			if($sxe && ($geoname = $sxe->geoname))
			{
				$ret = (string)$geoname->city.', '.(string)$geoname->country;
			}
		}
		return $ret;
	}

  public static function get_country_code($geonameid)
  {

    $url = 'http://localization.webservice.alchemyasp.com/get_name.php?geonameid='
            . $geonameid;

    $ret = '';

    $xml = p4::getUrl($url);
    if ($xml)
    {
      $sxe = simplexml_load_string($xml);

      if ($sxe && ($geoname = $sxe->geoname))
      {
        $ret = (string) $geoname->country_code;
      }
    }
    return $ret;
  }
		
}