<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class API_OAuth2_Exception_Redirect extends API_OAuth2_Exception_Exception
{
    /**
     *
     * @var int
     */
    protected $http_code = 302;

    /**
     *
     * @var string
     */
    protected $state;

    /**
     *
     * @var string
     */
    protected $redirect_uri;

    /**
     *
     * @param  string                        $redirect_uri
     * @param  string                        $error
     * @param  string                        $error_description
     * @param  string                        $state
     * @param  string                        $error_uri
     * @return API_OAuth2_Exception_Redirect
     */
    public function __construct($redirect_uri, $error, $error_description = null, $state = null, $error_uri = null)
    {
        $this->redirect_uri = $redirect_uri;
        $this->state = $state;
        parent::__construct($this->http_code, $error, $error_description, $error_uri);

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     *
     * @param  string                        $state
     * @return API_OAuth2_Exception_Redirect
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getRedirect_uri()
    {
        return $this->redirect_uri;
    }

    /**
     *
     * @param  string                        $redirect_uri
     * @return API_OAuth2_Exception_Redirect
     */
    public function setRedirect_uri($redirect_uri)
    {
        $this->redirect_uri = $redirect_uri;

        return $this;
    }
}
