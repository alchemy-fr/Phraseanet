<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_WWWAuthenticate extends API_OAuth2_Exception_Exception
{
    /**
     *
     * @var string
     */
    protected $realm;

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @param  int                                  $http_code
     * @param  string                               $realm
     * @param  string                               $error
     * @param  string                               $error_description
     * @param  string                               $error_uri
     * @param  string                               $scope
     * @return API_OAuth2_Exception_WWWAuthenticate
     */
    public function __construct($http_code, $realm, $error, $error_description = null, $error_uri = null, $scope = null)
    {
        $this->realm = $realm;
        $this->scope = $scope;

        parent::__construct($http_code, $error, $error_description, $error_uri);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getRealm()
    {
        return $this->realm;
    }

    /**
     *
     * @param  string                               $realm
     * @return API_OAuth2_Exception_WWWAuthenticate
     */
    public function setRealm($realm)
    {
        $this->realm = $realm;

        return $this;
    }
}
