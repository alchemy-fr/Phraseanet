<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_OAuth2_Exception_WWWAuthenticate_Type_BadRequest extends API_OAuth2_Exception_WWWAuthenticate
{
    /**
     *
     * @var int
     */
    protected $http_code = 400;

    /**
     *
     * @param  string                                               $realm
     * @param  string                                               $error
     * @param  string                                               $error_description
     * @param  string                                               $error_uri
     * @param  string                                               $scope
     * @return API_OAuth2_Exception_WWWAuthenticate_Type_BadRequest
     */
    public function __construct($realm, $error, $error_description = null, $error_uri = null, $scope = null)
    {
        parent::__construct($this->http_code, $realm, $error, $error_description = null, $error_uri, $scope);

        return $this;
    }
}
