<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_Exception extends Exception implements API_OAuth2_Exception_Interface
{
    /**
     *
     * @var int
     */
    protected $http_code;

    /**
     *
     * @var string
     */
    protected $error;

    /**
     *
     * @var string
     */
    protected $error_description;

    /**
     *
     * @var string
     */
    protected $error_uri;

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @param  int                            $http_code
     * @param  string                         $error
     * @param  string                         $error_description
     * @param  string                         $scope
     * @param  string                         $error_uri
     * @return API_OAuth2_Exception_Exception
     */
    public function __construct($http_code, $error, $error_description = null, $scope = null, $error_uri = null)
    {
        $this->error = $error;
        $this->error_description = $error_description;
        $this->scope = $scope;
        $this->error_uri = $error_uri;
        parent::__construct();

        return $this;
    }

    /**
     *
     * @return int
     */
    public function getHttp_code()
    {
        return $this->http_code;
    }

    /**
     *
     * @param  int                            $http_code
     * @return API_OAuth2_Exception_Exception
     */
    public function setHttp_code($http_code)
    {
        $this->http_code = $http_code;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     *
     * @param  string                         $error
     * @return API_OAuth2_Exception_Exception
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getError_description()
    {
        return $this->error_description;
    }

    /**
     *
     * @param  string                         $error_description
     * @return API_OAuth2_Exception_Exception
     */
    public function setError_description($error_description)
    {
        $this->error_description = $error_description;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getError_uri()
    {
        return $this->error_uri;
    }

    /**
     *
     * @param  string                         $error_uri
     * @return API_OAuth2_Exception_Exception
     */
    public function setError_uri($error_uri)
    {
        $this->error_uri = $error_uri;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     *
     * @param  string                         $scope
     * @return API_OAuth2_Exception_Exception
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
