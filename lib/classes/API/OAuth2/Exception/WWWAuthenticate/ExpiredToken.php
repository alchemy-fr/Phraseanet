<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_WWWAuthenticate_ExpiredToken extends API_OAuth2_Exception_WWWAuthenticate_Type_Unauthorized
{
    /**
     *
     * @var string
     */
    protected $error = 'expired_token';

    /**
     *
     * @var string
     */
    protected $error_description = "The access token provided has expired.";

    /**
     *
     * @param  string                                            $realm
     * @param  string                                            $scope
     * @param  string                                            $error_uri
     * @return API_OAuth2_Exception_WWWAuthenticate_ExpiredToken
     */
    public function __construct($realm, $scope = null, $error_uri = null)
    {
        parent::__construct($realm, $this->error, $this->error_description, $error_uri, $scope);

        return $this;
    }
}
