<?php
/**
 * DiggBar adapter
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/

class PHPShortenerDiggCom extends PHPShortenerService {
	
	var $service = 'digg.com';
	
	var $appkey = null;
	
	/**
	 * Encode function
	 *
	 * @param string $url URL to encode
	 * @return mixed Encoded URL or false (if failed)
	 * @author Guillermo Rauch
	 */
	function encode($url){
		$url = sprintf('http://services.digg.com/url/short/create?url=%s&type=xml&appkey=%s', urlencode($url), urlencode($this->appkey ? $this->appkey : 'http://' . $_SERVER['SERVER_NAME']));
		$response = $this->fetch($url);
		if (preg_match('/short_url="([^"]*)/', $response, $results)) return $results[1];
		return false;
	}
	
	/**
	 * Default decoding function
	 *
	 * @param string $url Url to decode
	 * @return void
	 * @author Guillermo Rauch
	 */
	function decode($hash){
		$url = sprintf('http://services.digg.com/url/short/%s?appkey=%s&type=xml', $hash, urlencode($this->appkey ? $this->appkey : 'http://' . $_SERVER['SERVER_NAME']));
		$response = $this->fetch($url);
		if (preg_match('/link="([^"]*)/', $response, $results)) return $results[1];
		return false;
	}
	
}
?>