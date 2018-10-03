<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_WWWAuthenticate_InsufficientScope extends API_OAuth2_Exception_WWWAuthenticate_Type_Forbidden
{
    /**
     *
     * @var string
     */
    protected $error = 'insufficient_scope';

    /**
     *
     * @var string
     */
    protected $error_description = "The request requires higher privileges than provided by the access token.";

    /**
     *
     * @param  string                                                 $realm
     * @param  string                                                 $scope
     * @param  string                                                 $error_uri
     * @return API_OAuth2_Exception_WWWAuthenticate_InsufficientScope
     */
    public function __construct($realm, $scope = null, $error_uri = null)
    {
        parent::__construct($realm, $this->error, $this->error_description, $error_uri, $scope);

        return $this;
    }
}
