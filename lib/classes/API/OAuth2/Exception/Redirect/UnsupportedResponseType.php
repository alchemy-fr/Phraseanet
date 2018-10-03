<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_Redirect_UnsupportedResponseType extends API_OAuth2_Exception_Redirect
{
    /**
     *
     * @var string
     */
    protected $error = 'unsupported_response_type';

    /**
     *
     * @var string
     */
    protected $error_description = "The authorization server does not support obtaining an authorization code using this method.";

    /**
     *
     * @var string
     */
    protected $method;

    /**
     *
     * @param  string                                                $redirect_uri
     * @param  string                                                $method
     * @param  string                                                $state
     * @param  string                                                $error_uri
     * @return API_OAuth2_Exception_Redirect_UnsupportedResponseType
     */
    public function __construct($redirect_uri, $method, $state = null, $error_uri = null)
    {
        $this->method = $method;
        parent::__construct($redirect_uri, $this->error, $this->error_description, $state, $error_uri);

        return $this;
    }

    /**
     *
     * @var string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     *
     * @param  string                                                $method
     * @return API_OAuth2_Exception_Redirect_UnsupportedResponseType
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }
}
