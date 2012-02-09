<?php

class geonames implements cache_cacheableInterface
{

  protected static $NamesFromId = array();
  protected static $CountryFromId = array();
  protected static $CountryCodeFromId = array();
  protected static $GeonameFromIp = array();
  protected static $Searches = array();

  const CACHE_NAMESFROMID       = 'NAMESFROMID';
  const CACHE_COUNTRYFROMID     = 'COUNTRYFROMID';
  const CACHE_COUNTRYCODEFROMID = 'COUNTRYCODEFROMID';
  const CACHE_GEONAMEFROMIP     = 'GEONAMEFROMIP';
  const CACHE_SEARCH            = 'SEARCH';

  public function name_from_id($geonameid)
  {
    if (trim($geonameid) === '')
    {
      return null;
    }

    if (!ctype_digit($geonameid))
    {
      return null;
    }

    $cache_id = self::CACHE_NAMESFROMID . '_' . $geonameid;

    try
    {
      return $this->get_data_from_cache($cache_id);
    }
    catch (\Exception $e)
    {

    }

    $registry = registry::get_instance();
    $url      = $registry->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
      . 'get_name.php?geonameid='
      . $geonameid;

    $ret = '';

    $xml = http_query::getUrl($url);
    if ($xml)
    {

      $sxe = simplexml_load_string($xml);

      if ($sxe && ($geoname = $sxe->geoname))
      {
        $ret = (string) $geoname->city . ', ' . (string) $geoname->country;
      }
    }

    $this->set_data_to_cache($ret, $cache_id);

    return $ret;
  }

  public function get_country($geonameid)
  {
    if (trim($geonameid) === '' || !ctype_digit($geonameid))
    {
      return '';
    }

    $cache_id = self::CACHE_COUNTRYFROMID . '_' . $geonameid;

    try
    {
      return $this->get_data_from_cache($cache_id);
    }
    catch (\Exception $e)
    {

    }

    $registry = registry::get_instance();
    $url      = $registry->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
      . 'get_name.php?geonameid='
      . $geonameid;

    $ret = '';
    $xml = http_query::getUrl($url);
    if ($xml)
    {
      $sxe = simplexml_load_string($xml);

      if ($sxe && ($geoname = $sxe->geoname))
      {
        $ret = (string) $geoname->country;
      }
    }

    $this->set_data_to_cache($ret, $cache_id);

    return $ret;
  }

  public function get_country_code($geonameid)
  {
    if (trim($geonameid) === '')
    {
      return null;
    }

    if (!ctype_digit($geonameid))
    {
      return null;
    }

    $cache_id = self::CACHE_COUNTRYCODEFROMID . '_' . $geonameid;

    try
    {
      return $this->get_data_from_cache($cache_id);
    }
    catch (\Exception $e)
    {

    }

    $registry = registry::get_instance();
    $url      = $registry->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
      . 'get_name.php?geonameid='
      . $geonameid;

    $ret = '';

    $xml = http_query::getUrl($url);
    if ($xml)
    {
      $sxe = simplexml_load_string($xml);

      if ($sxe && ($geoname = $sxe->geoname))
      {
        $ret = (string) $geoname->country_code;
      }
    }

    $this->set_data_to_cache($ret, $cache_id);

    return $ret;
  }

  protected static function clean_input($input)
  {
    return strip_tags(trim($input));
  }

  protected static function highlight($title, $length)
  {
    return '<span class="highlight">' . mb_substr($title, 0, $length) . '</span>'
      . mb_substr($title, $length);
  }

  public function find_city($cityName)
  {
    $output = array();
    $cityName = self::clean_input($cityName);

    if (strlen($cityName) === 0)
      return $output;

    $cache_id = self::CACHE_SEARCH . '_' . $cityName;

    try
    {
      return $this->get_data_from_cache($cache_id);
    }
    catch (\Exception $e)
    {

    }


    $registry = registry::get_instance();
    $url      = $registry->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
      . 'find_city.php?city='
      . urlencode($cityName) . '&maxResult=30';

    $sxe = simplexml_load_string(http_query::getUrl($url));

    foreach ($sxe->geoname as $geoname)
    {
      $length = mb_strlen($geoname->title_match);

      $title_highlight = self::highlight($geoname->title, $length);

      $country_highlight = (string) $geoname->country;
      if (trim($geoname->country_match) !== '')
      {
        $length            = mb_strlen($geoname->country_match);
        $country_highlight = self::highlight($geoname->country, $length);
      }

      $output[] = array(
        'title_highlighted'   => $title_highlight
        , 'title'               => (string) $geoname->title
        , 'country_highlighted' => $country_highlight
        , 'country'             => (string) $geoname->country
        , 'geoname_id'          => (int) $geoname->geonameid
        , 'region'              => (string) $geoname->region
      );
    }

    $this->set_data_to_cache($output, $cache_id);

    return $output;
  }

  protected $cache_ips = array();

  public function find_geoname_from_ip($ip)
  {
    if (array_key_exists($ip, $this->cache_ips))
      return $this->cache_ips[$ip];


    if (trim($ip) === '')
    {
      return null;
    }

    $cache_id = self::CACHE_GEONAMEFROMIP . '_' . $ip;

    try
    {
      return $this->get_data_from_cache($cache_id);
    }
    catch (\Exception $e)
    {

    }

    $output = array(
      'city'         => '',
      'country_code' => '',
      'country'      => '',
      'fips'         => '',
      'longitude'    => '',
      'latitude'     => ''
    );

    $registry = registry::get_instance();
    $url      = $registry->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
      . 'geoip.php?ip='
      . urlencode($ip);

    $xml = http_query::getUrl($url);
    if ($xml)
    {
      $sxe = simplexml_load_string($xml);
      if ($sxe && $sxe->geoname)
      {
        $output['city']         = (string) $sxe->geoname->city;
        $output['country_code'] = (string) $sxe->geoname->country_code;
        $output['country']      = (string) $sxe->geoname->country;
        $output['fips']         = (string) $sxe->geoname->fips;
        $output['longitude']    = (string) $sxe->geoname->longitude;
        $output['latitude']     = (string) $sxe->geoname->latitude;
      }
    }
    $this->cache_ips[$ip] = $output;

    $this->set_data_to_cache($output, $cache_id);

    return $output;
  }

  public function get_cache_key($option = null)
  {
    return 'geonames_' . ($option ? '_' . $option : '');
  }

  public function get_data_from_cache($option = null)
  {
    $appbox = appbox::get_instance();
    $datas =  $appbox->get_data_from_cache($this->get_cache_key($option));

    echo "got form cache\n";
    return $datas;
    }

  public function set_data_to_cache($value, $option = null, $duration = 0)
  {
    $appbox = appbox::get_instance();
    return $appbox->set_data_to_cache($value, $this->get_cache_key($option), $duration);
  }

  public function delete_data_from_cache($option = null)
  {
    $appbox = appbox::get_instance();
    return $appbox->delete_data_from_cache($this->get_cache_key($option));
  }

}
