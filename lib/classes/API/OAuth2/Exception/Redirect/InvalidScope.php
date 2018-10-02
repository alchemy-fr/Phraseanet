<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_Redirect_InvalidScope extends API_OAuth2_Exception_Redirect
{
    /**
     *
     * @var string
     */
    protected $error = 'invalid_scope';

    /**
     *
     * @var string
     */
    protected $error_description = "The requested scope is invalid, unknown, or malformed.";

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @param  string                                     $redirect_uri
     * @param  string                                     $scope
     * @param  string                                     $state
     * @param  string                                     $error_uri
     * @return API_OAuth2_Exception_Redirect_InvalidScope
     */
    public function __construct($redirect_uri, $scope, $state = null, $error_uri = null)
    {
        $this->scope = $scope;
        parent::__construct($redirect_uri, $this->error, $this->error_description, $state, $error_uri);

        return $this;
    }

    /**
     *
     * @var string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     *
     * @param  string                                     $scope
     * @return API_OAuth2_Exception_Redirect_InvalidScope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }
}
