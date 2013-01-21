<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class geonames
{
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function name_from_id($geonameid)
    {
        $url = $this->app['phraseanet.registry']->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
            . 'get_name.php?geonameid='
            . $geonameid;

        $ret = '';

        $xml = http_query::getUrl($url);
        if ($xml) {

            $sxe = simplexml_load_string($xml);

            if ($sxe !== false && ($geoname = $sxe->geoname)) {
                $ret = (string) $geoname->city . ', ' . (string) $geoname->country;
            }
        }

        return $ret;
    }

    public function get_country($geonameid)
    {
        if (trim($geonameid) === '' || trim($geonameid) <= 0) {
            return '';
        }

        $url = $this->app['phraseanet.registry']->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
            . 'get_name.php?geonameid='
            . $geonameid;

        $ret = '';
        $xml = http_query::getUrl($url);
        if ($xml) {
            $sxe = simplexml_load_string($xml);

            if ($sxe !== false && ($geoname = $sxe->geoname)) {
                $ret = (string) $geoname->country;
            }
        }

        return $ret;
    }

    public function get_country_code($geonameid)
    {
        $url = $this->app['phraseanet.registry']->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
            . 'get_name.php?geonameid='
            . $geonameid;

        $ret = '';

        $xml = http_query::getUrl($url);
        if ($xml) {
            $sxe = simplexml_load_string($xml);

            if ($sxe !== false && ($geoname = $sxe->geoname)) {
                $ret = (string) $geoname->country_code;
            }
        }

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

        if (strlen($cityName) === 0) {
            return $output;
        }

        $url = $this->app['phraseanet.registry']->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
            . 'find_city.php?city='
            . urlencode($cityName) . '&maxResult=30';

        $sxe = simplexml_load_string(http_query::getUrl($url));

        foreach ($sxe->geoname as $geoname) {
            $length = mb_strlen($geoname->title_match);

            $title_highlight = self::highlight($geoname->title, $length);

            $country_highlight = (string) $geoname->country;
            if (trim($geoname->country_match) !== '') {
                $length = mb_strlen($geoname->country_match);
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

        return $output;
    }
    protected $cache_ips = array();

    public function find_geoname_from_ip($ip)
    {
        if (array_key_exists($ip, $this->cache_ips)) {
            return $this->cache_ips[$ip];
        }

        $output = array(
            'city'         => '',
            'country_code' => '',
            'country'      => '',
            'fips'         => '',
            'longitude'    => '',
            'latitude'     => ''
        );

        $url = $this->app['phraseanet.registry']->get('GV_i18n_service', 'http://localization.webservice.alchemyasp.com/')
            . 'geoip.php?ip='
            . urlencode($ip);

        $xml = http_query::getUrl($url);
        if ($xml) {
            $sxe = simplexml_load_string($xml);
            if ($sxe !== false && $sxe->geoname) {
                $output['city'] = (string) $sxe->geoname->city;
                $output['country_code'] = (string) $sxe->geoname->country_code;
                $output['country'] = (string) $sxe->geoname->country;
                $output['fips'] = (string) $sxe->geoname->fips;
                $output['longitude'] = (string) $sxe->geoname->longitude;
                $output['latitude'] = (string) $sxe->geoname->latitude;
            }
        }
        $this->cache_ips[$ip] = $output;

        return $output;
    }
}
