<?php
/**
 * twurl.nl adapter
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/

class PHPShortenerTwurlNl extends PHPShortenerService {
	
	var $service = 'twurl.nl';
	
	/**
	 * Encode function
	 *
	 * @todo Use CURL if present to post.
	 * @param string $url URL to encode
	 * @return mixed Encoded URL or false (if failed)
	 * @author Guillermo Rauch
	 */
	function encode($url){
		$data = 'link[url]=' . urlencode($url);
		$params = array('http' => array( 'method' => 'POST', 'content' => $data));
    $ctx = stream_context_create($params);
    $fp = @fopen('http://tweetburner.com/links', 'rb', false, $ctx);
    if (!$fp) return false;
    $response = @stream_get_contents($fp);
    if ($response === false) return false;
    return $response;		
	}
	
	/**
	 * Default decoding function
	 *
	 * @param string $url Url to decode
	 * @return void
	 * @author Guillermo Rauch
	 */
	function decode($hash){
		$headers = $this->getHeaders(sprintf('http://%s/%s', $this->service, $hash));
		if (!$headers || !strstr($headers[0], '303')) return false;
		return $headers['Location'];
	}
	
}
?>