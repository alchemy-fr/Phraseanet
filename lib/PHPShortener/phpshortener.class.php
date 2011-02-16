<?php
/**
 * PHP Shortener class. Encodes and decodes URLs
 *
 * @author Guillermo Rauch
 * @version $Id$
 * @copyright Devthought, 24 April, 2009
 * @package phpshortener
 **/


class PHPShortener {
	
	/**
	 * Supported services
	 *
	 * @author Guillermo Rauch
	 */
	
	
	var $services = array(
		'bit.ly',
		'is.gd',
		'tinyurl.com',
		'tr.im',
		'twurl.nl',
		'digg.com',
		'u.nu'
	);
	
	/**
	 * Stored adapters
	 *
	 * @access private
	 * @author Guillermo Rauch
	 */
	var $adapters = array();
	
	/**
	 * Encodes a long url into a short one, with the service specified
	 *
	 * @param string $url URL to encode
	 * @param string $service Service to use from the supported above
	 * @return string Shortened URL, or passed URL if couldn't be shortened
	 * @author Guillermo Rauch
	 */
	function encode($url, $service = 'bit.ly'){
		if (!in_array($service, $this->services)) return false;
		$adapter = $this->getAdapter($service);
		$encoded = $adapter->encode($url);
		return $encoded ? $encoded : $url;
	}
	
	/**
	 * Decode a URL
	 *
	 * @param string $url Short url
	 * @return string Original url
	 * @author Guillermo Rauch
	 */
	function decode($url){
		if (preg_match('/^(http:\/\/)?(www\.)?([^\/]*)\/(.*)$/', $url, $results)){
			if ($results[3] && $results[4]){
				$service = $results[3];
				$hash = $results[4];
				$adapter = $this->getAdapter($service);
				return $adapter->decode($hash);
			}
		} 
		return false;
	}
	
	/**
	 * Returns the adapter instance for the service
	 *
	 * @param string $service Service
	 * @return object Adapter
	 * @author Guillermo Rauch
	 */
	function &getAdapter($service){
		if (isset($this->adapters[$service])) return $this->adapters[$service];
		require_once(sprintf('%s/services/%s.php', dirname(__FILE__), $service));
		$class = $this->toClass($service);
		$adapter = new $class;
		$this->adapters[$service] = &$adapter;
		return $adapter;
	}
	
	/**
	 * Comes up with the class name for the service (is.gd => IsGd)
	 *
	 * @param string $service Service to parse
	 * @return string Class name
	 * @author Guillermo Rauch
	 */
	function toClass($service){
		$class = '';
		$parts = explode('.', strtolower($service));
		foreach ($parts as $part) $class .= ucfirst($part);
		return 'PHPShortener' . $class;
	}
	
}

/**
 * Abstract class for each service
 *
 * @package phpshortener
 * @author Guillermo Rauch
 */
class PHPShortenerService {
	
	/**
	 * Default decoding function
	 *
	 * @param string $url Url to decode
	 * @return void
	 * @author Guillermo Rauch
	 */
	function decode($hash){
		$headers = $this->getHeaders(sprintf('http://%s/%s', $this->service, $hash));
		if (!$headers || !strstr($headers[0], '301')) return false;
		return $headers['Location'];
	}
	
	/**
	 * Fetches the content of an URL, using curl or fopen
	 *
	 * @param string $url URL to fetch
	 * @return string Contents
	 * @author Guillermo Rauch
	 */
	function fetch($url){
		if (function_exists('curl_init')){
			$c = curl_init();
      curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($c, CURLOPT_URL, $url);
			curl_setopt($c, CURLOPT_USERAGENT, ini_get('user_agent') ? ini_get('user_agent') : 'PHP');
      $contents = curl_exec($c);
      curl_close($c);
			return $contents;
		} elseif (function_exists('file_get_contents')) {
			return @file_get_contents($url);
		} else {
			if (false === $fh = @fopen($url, 'rb')) return false;
      clearstatcache();
      if ($fsize = @filesize($url)) {
	      $contents = fread($fh, $fsize);
      } else {
	      $contents = '';
	      while (!feof($fh)) $contents .= fread($fh, 8192);
      }
      fclose($fh);
      return $contents;
		}
	}
	
	/**
	 * Gets a URL headers
	 *
	 * @param string $url URL to check
	 * @return array Headers of the response
	 * @author Guillermo Rauch
	 */
	function getHeaders($url){
    $headers = array();
    $url = parse_url($url);
    $host = isset($url['host']) ? $url['host'] : '';
    $port = isset($url['port']) ? $url['port'] : 80;
    $path = (isset($url['path']) ? $url['path'] : '/') . (isset($url['query']) ? '?' . $url['query'] : '');
    $fp = fsockopen($host, $port, $errno, $errstr, 3);
    if ($fp){
	    $hdr = "GET $path HTTP/1.1\r\n";
	    $hdr .= "Host: $host \r\n";
	    $hdr .= "Connection: Close\r\n\r\n";
	    fwrite($fp, $hdr);
	    while (!feof($fp) && $line = trim(fgets($fp, 1024))){
	      if ($line == "\r\n") break;
				if (strstr($line, ':')){
				  list($key, $val) = explode(': ', $line, 2);
	        if ($val) $headers[$key] = $val;
	        else $headers[] = $key;	
				} else {
					$headers[] = $line;
				}
	    }
	    fclose($fp);
	    return $headers;
    }
    return false;
	}
	
}
?>