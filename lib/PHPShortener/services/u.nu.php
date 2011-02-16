<?php
/**
 * u.nu adapter
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/

class PHPShortenerUNu extends PHPShortenerService {
	
	var $service = 'u.nu';
	
	var $apikey = false;
	
	/**
	 * Encode function
	 *
	 * @param string $url URL to encode
	 * @return mixed Encoded URL or false (if failed)
	 * @author Guillermo Rauch
	 */
	function encode($url){
		return trim($this->fetch('http://u.nu/unu-api-simple?url=' . urlencode($url)));
	}
	
}
?>