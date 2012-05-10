<?php

/**
 * Exception related to W3CFeedValidator object
 */
class W3CFeedValidatorException extends Exception
{

}

/**
 * Use W3C API FEED VALIDATOR
 * @see http://validator.w3.org/feed/docs/soap.html
 */
abstract class W3CFeedValidator
{
    const VALIDATOR_ENDPOINT = "http://validator.w3.org/feed/check.cgi";
    const OUTPOUT = "soap12";
    const TIME_BETWEEN_REQUEST = 2;

    public abstract function validate();
}

/**
 * Use W3C API to valide your RSS FEED identified by an URL
 * @see http://validator.w3.org/feed/docs/soap.html
 */
class W3CFeedUrlValidator extends W3CFeedValidator
{
    /**
     * The URL of the document to validate
     * @var string
     */
    private $url;

    /**
     * Constructor
     * Init instance with a specified url which identify a XML to validate
     * @param string $url
     * @throws W3CFeedValidatorException if url is not valid
     */
    public function __construct($url)
    {
        if ( ! $this->isValidUrl($url))
            throw new W3CFeedValidatorException(sprintf("Invalid url %s", $url));

        $this->url = $url;
    }

    /**
     * Validate the xml feed located at the current url
     * @param string $feed_url
     * @return W3CFeedValidatorResponse
     * @throws W3CFeedValidatorException if the request failed
     */
    public function validate()
    {
        $url_validator = sprintf("%s?url=%s&output=%s", self::VALIDATOR_ENDPOINT, $this->url, self::OUTPOUT);
        sleep(self::TIME_BETWEEN_REQUEST);
        $response = @file_get_contents($url_validator);
        if ( ! $response) {
            throw new W3CFeedValidatorException("Unable to request W3C API");
        }

        return new W3CFeedValidatorResponse($response);
    }

    /**
     * Check if an url is valid
     * @param string $url
     * @return boolean
     */
    private function isValidUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}

/**
 *
 * @example
 *
 * try
 * {
 *     $validator = new W3CFeedRawValidator($xml);
 *     $response = $validator->validate();
 *
 *     if($response->isValid())
 *     {
 *         //do something
 *     }
 *
 *     $validator = new W3CFeedUrlValidator($url);
 *     $response = $validator->validate();
 *
 *     if($response->isValid())
 *     {
 *         //do something
 *     }
 * }
 * catch (W3CFeedValidatorException $e)
 * {
 *     print "\n" . $e->getMessage();
 * }
 *
 * Use W3C API to valide your RSS FEED
 * @see http://validator.w3.org/feed/docs/soap.html
 */
class W3CFeedRawValidator extends W3CFeedValidator
{
    /**
     * The source of the document to validate
     * @var string
     */
    private $rawData;

    /**
     * Constructor
     * Init instance which the specified data to validate
     * @param string $rawData
     */
    public function __construct($rawData)
    {
        $this->rawData = utf8_encode(trim($rawData));
    }

    /**
     * Validate the rawData xml
     * @param string $feed_url
     * @return W3CFeedValidatorResponse
     * @throws W3CFeedValidatorException if the request failed
     */
    public function validate()
    {
        $context = stream_context_create(array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query(array(
                    'rawdata' => $this->rawData,
                    'manual'  => 1,
                    'output'  => self::OUTPOUT)),
                'timeout' => 5,
            ),
            ));
        sleep(self::TIME_BETWEEN_REQUEST);
        $response = @file_get_contents(self::VALIDATOR_ENDPOINT, false, $context);
        if ( ! $response) {
            throw new W3CFeedValidatorException("Unable to request W3C API");
        }

        return new W3CFeedValidatorResponse($response);
    }
}

/**
 * An object that handle the W3C FEED VALIDATOR response
 */
class W3CFeedValidatorResponse
{
    /**
     * XML W3C response
     * @var string
     */
    private $xml;

    /**
     * @var DOMDocument
     */
    private $DOMDocument;

    /**
     * @var DOMXPath
     */
    private $DOMXpath;

    /**
     * Constructor
     * Init instance with the W3C FEED VALIDATOR xml response
     * @param string $xml
     * @throws W3CFeedValidatorException if xml can't be loaded
     */
    public function __construct($xml)
    {
        $this->DOMDocument = new DOMDocument();
        if ( ! $this->DOMDocument->loadXML($xml)) {
            throw new W3CFeedValidatorException("Unable to parse XML");
        }
        $this->DOMXpath = new DOMXPath($this->DOMDocument);
        $this->DOMXpath->registerNamespace("env", "http://www.w3.org/2003/05/soap-envelope");
        $this->DOMXpath->registerNamespace("m", "http://www.w3.org/2005/10/feed-validator");

        $this->xml = $xml;
    }

    public function __toString()
    {
        $string = "";
        foreach ($this->getErrors() as $error) {
            $string .= "\nERROR\n";
            foreach ($error as $name => $detail) {
                $string .= $name . "=>" . $detail . "\n";
            }
        }

        return $string;
    }

    /**
     * Check if the XML is valid
     * @return boolean
     */
    public function isValid()
    {
        $xPathQuery = "/env:Envelope/env:Body/m:feedvalidationresponse/m:validity";

        return $this->DOMXpath->query($xPathQuery)->item(0)->nodeValue !== "false";
    }

    /**
     * Check for errors
     * @return boolean
     */
    public function hasError()
    {
        $xPathQuery = "/env:Envelope/env:Body/m:feedvalidationresponse/m:errors/m:errorcount";

        return (int) $this->DOMXpath->query($xPathQuery)->item(0)->nodeValue !== 0;
    }

    /**
     * check fro warnings
     * @return boolean
     */
    public function hasWarning()
    {
        $xPathQuery = "/env:Envelope/env:Body/m:feedvalidationresponse/m:warnings/m:warningcount";

        return (int) $this->DOMXpath->query($xPathQuery)->item(0)->nodeValue !== 0;
    }

    /**
     * Get an array with all warnings as follow
     * array{
     *  0 => array {
     *    array(10) {
     *        ["level"]=>
     *        string(7) "warning"
     *        ["type"]=>
     *        string(23) "SelfDoesntMatchLocation"
     *        ["line"]=>
     *        string(2) "11"
     *        ["column"]=>
     *        string(2) "91"
     *        ["text"]=>
     *        string(46) "Self reference doesn't match document location"
     *        ["msgcount"]=>
     *        string(1) "1"
     *        ["backupcolumn"]=>
     *        string(2) "91"
     *        ["backupline"]=>
     *        string(2) "11"
     *       ["element"]=>
     *        string(4) "href"
     *        ["parent"]=>
     *        string(7) "channel"
     *   }
     *  1 => etc ..
     *  }
     * }
     *
     * @return array
     */
    public function getWarnings()
    {
        $warnings = array();
        $xPathQuery = "/env:Envelope/env:Body/m:feedvalidationresponse/m:warnings/m:warninglist/*";
        $warningList = $this->DOMXpath->query($xPathQuery);
        if ($warningList->length > 0) {
            foreach ($warningList as $warning) {
                $warnings[] = $this->domNodeToArray($warning);
            }
        }

        return $warnings;
    }

    /**
     * Get an array with all errors as follow
     * @return array
     */
    public function getErrors()
    {
        $errors = array();
        $xPathQuery = "/env:Envelope/env:Body/m:feedvalidationresponse/m:errors/m:errorlist/*";
        $errorList = $this->DOMXpath->query($xPathQuery);
        if ($errorList->length > 0) {
            foreach ($errorList as $error) {
                $errors[] = $this->domNodeToArray($error);
            }
        }

        return $errors;
    }

    /**
     * @source http://www.ermshaus.org/2010/12/php-transform-domnode-to-array
     *
     * Transforms the contents of a DOMNode to an associative array
     *
     * XML node names become array keys, attributes will be discarded. If $node
     * doesn't contain child nodes, a string will be returned.
     *
     * @param DOMNode $node DOMDocument node
     * @return string|array Associative array or string with node content
     */
    private function domNodeToArray(DOMNode $node)
    {
        $ret = '';

        if ($node->hasChildNodes()) {
            if ($node->firstChild === $node->lastChild && $node->firstChild->nodeType === XML_TEXT_NODE) {
                // Node contains nothing but a text node, return its value
                $ret = trim($node->nodeValue);
            } else {
                // Otherwise, do recursion
                $ret = array();
                foreach ($node->childNodes as $child) {
                    if ($child->nodeType !== XML_TEXT_NODE) {
                        // If there's more than one node with this node name on the
                        // current level, create an array
                        if (isset($ret[$child->nodeName])) {
                            if ( ! is_array($ret[$child->nodeName]) || ! isset($ret[$child->nodeName][0])) {
                                $tmp = $ret[$child->nodeName];
                                $ret[$child->nodeName] = array();
                                $ret[$child->nodeName][] = $tmp;
                            }

                            $ret[$child->nodeName][] = $this->domNodeToArray($child);
                        } else {
                            $ret[$child->nodeName] = $this->domNodeToArray($child);
                        }
                    }
                }
            }
        }

        return $ret;
    }
}
