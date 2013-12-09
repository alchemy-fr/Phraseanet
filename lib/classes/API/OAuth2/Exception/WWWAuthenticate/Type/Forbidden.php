<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_WWWAuthenticate_Type_Forbidden extends API_OAuth2_Exception_WWWAuthenticate
{

    /**
     *
     * @param  string                                              $realm
     * @param  string                                              $error
     * @param  string                                              $error_description
     * @param  string                                              $error_uri
     * @param  string                                              $scope
     * @return API_OAuth2_Exception_WWWAuthenticate_Type_Forbidden
     */
    public function __construct($realm, $error, $error_description = null, $error_uri = null, $scope = null)
    {
        parent::__construct($realm, $error, $error_description = null, $error_uri, $scope);
        $this->setHttp_code(403);

        return $this;
    }
}
