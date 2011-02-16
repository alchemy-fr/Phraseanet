<?php
/**
 * tr.im adapter
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/

class PHPShortenerTrIm extends PHPShortenerService {
	
	var $service = 'tr.im';
	
	var $apikey = false;
	
	/**
	 * Encode function
	 *
	 * @param string $url URL to encode
	 * @return mixed Encoded URL or false (if failed)
	 * @author Guillermo Rauch
	 */
	function encode($url){
		$url = sprintf('http://tr.im/api/trim_url.xml?%s&url=%s', $this->apikey ? 'api_key=' . $this->apikey : '', urlencode($url));
		$response = $this->fetch($url);
		if (preg_match('/<url>([^<]*)/', $response, $results)) return $results[1];
		return false;
	}
	
}
?>