<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_WWWAuthenticate_InvalidRequest extends API_OAuth2_Exception_WWWAuthenticate_Type_BadRequest
{
    /**
     *
     * @var string
     */
    protected $error = 'invalid_request';

    /**
     *
     * @var string
     */
    protected $error_description = "The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.";

    /**
     *
     * @param  string                                              $realm
     * @param  string                                              $scope
     * @param  string                                              $error_uri
     * @return API_OAuth2_Exception_WWWAuthenticate_InvalidRequest
     */
    public function __construct($realm, $scope = null, $error_uri = null)
    {
        parent::__construct($realm, $this->error, $this->error_description, $error_uri, $scope);

        return $this;
    }
}
