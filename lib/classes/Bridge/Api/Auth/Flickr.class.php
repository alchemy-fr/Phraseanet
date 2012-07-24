<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Bridge
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Bridge_Api_Auth_Flickr extends Bridge_Api_Auth_Abstract implements Bridge_Api_Auth_Interface
{
    /**
     *
     * @var string
     */
    protected $flickr_client_id;

    /**
     *
     * @var string
     */
    protected $flickr_client_secret;

    /**
     *
     * @var string
     */
    protected $permissions;

    /**
     *
     * @var Phlickr_Api
     */
    protected $_api;

    /**
     *
     * @return Phlickr_Api
     */
    protected function get_api()
    {
        if ( ! $this->_api) {
            $this->_api = new Phlickr_Api(
                    $this->flickr_client_id,
                    $this->flickr_client_secret
            );
        }

        return $this->_api;
    }

    /**
     *
     * @return string
     */
    public function parse_request_token()
    {
        return isset($_GET["frob"]) ? $_GET["frob"] : null;
    }

    /**
     *
     * @param  string $param
     * @return Array
     */
    public function connect($param)
    {
        $auth_token = $this->get_api()->setAuthTokenFromFrob($param);
        if ( ! $this->get_api()->isAuthValid())
            throw new Bridge_Exception_ApiConnectorAccessTokenFailed();

        $this->get_api()->setAuthToken($auth_token);

        return array('auth_token' => $auth_token);
    }

    /**
     *
     * @return Bridge_Api_Auth_Flickr
     */
    public function reconnect()
    {
        return $this;
    }

    /**
     *
     * @return Bridge_Api_Auth_Flickr
     */
    public function disconnect()
    {
        $this->settings->set('auth_token', null);

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function is_connected()
    {
        return $this->settings->get('auth_token') !== null; // && $this->get_api()->isAuthValid();
    }

    /**
     *
     * @return Array
     */
    public function get_auth_signatures()
    {
        return array(
            'auth_token' => $this->settings->get('auth_token')
        );
    }

    /**
     *
     * @param  array                  $parameters
     * @return Bridge_Api_Auth_Flickr
     */
    public function set_parameters(Array $parameters)
    {
        $avail_parameters = array('flickr_client_id', 'flickr_client_secret', 'permissions');
        foreach ($parameters as $parameter => $value) {
            if ( ! in_array($parameter, $avail_parameters))
                continue;

            $this->$parameter = $value;
        }

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_auth_url(Array $supp_params = array())
    {
        $request_token = $this->get_api()->requestFrob();

        return $this->get_api()->buildAuthUrl($this->permissions, $request_token);
    }
}
