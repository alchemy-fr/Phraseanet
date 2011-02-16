<?php
/**
 * tinyurl.com adapter
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/

class PHPShortenerTinyurlCom extends PHPShortenerService {
	
	var $service = 'tinyurl.com';
	
	/**
	 * Encode function
	 *
	 * @param string $url URL to encode
	 * @return mixed Encoded URL or false (if failed)
	 * @author Guillermo Rauch
	 */
	function encode($url){
		return $this->fetch('http://tinyurl.com/api-create.php?url=' . urlencode($url));
	}
	
}
?>