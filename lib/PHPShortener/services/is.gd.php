<?php
/**
 * is.gd adapter
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/

class PHPShortenerIsGd extends PHPShortenerService {
	
	var $service = 'is.gd';
	
	/**
	 * Encode function
	 *
	 * @param string $url URL to encode
	 * @return mixed Encoded URL or false (if failed)
	 * @author Guillermo Rauch
	 */
	function encode($url){
		return $this->fetch('http://is.gd/api.php?longurl=' . urlencode($url));
	}
	
}
?>