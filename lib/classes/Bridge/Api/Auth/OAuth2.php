<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Auth_OAuth2 extends Bridge_Api_Auth_Abstract implements Bridge_Api_Auth_Interface
{
    /**
     *
     * @var string
     */
    protected $client_id;

    /**
     *
     * @var string
     */
    protected $client_secret;

    /**
     *
     * @var string
     */
    protected $redirect_uri;

    /**
     *
     * @var string
     */
    protected $scope;

    /**
     *
     * @var string
     */
    protected $response_type;

    /**
     *
     * @var string
     */
    protected $token_endpoint;

    /**
     *
     * @var string
     */
    protected $auth_endpoint;

    /**
     *
     * @var string
     */
    public function parse_request_token()
    {
        return isset($_GET[$this->response_type]) ? $_GET[$this->response_type] : null;
    }

    /**
     *
     * @param  string $request_token
     * @return Array
     */
    public function connect($request_token)
    {
        $post_params = [
            'code'          => $request_token,
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'redirect_uri'  => $this->redirect_uri,
            'grant_type'    => 'authorization_code'
        ];

        $response_json = http_query::getUrl($this->token_endpoint, $post_params);
        $response = json_decode($response_json, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS);

        if ( ! is_array($response) || ! isset($response['refresh_token']) || ! isset($response['access_token']))
            throw new Bridge_Exception_ApiConnectorAccessTokenFailed('Unable to retrieve tokens');

        return ['refresh_token' => $response['refresh_token'], 'auth_token'    => $response['access_token']];
    }

    /**
     *
     * @return Bridge_Api_Auth_OAuth2
     */
    public function reconnect()
    {
        $post_params = [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'refresh_token' => $this->settings->get('refresh_token'),
            'grant_type'    => 'refresh_token'
        ];

        $response = http_query::getUrl($this->token_endpoint, $post_params);
        $response = json_decode($response, JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_APOS);

        if ( ! is_array($response) || ! isset($response['access_token']))
            throw new Bridge_Exception_ApiConnectorAccessTokenFailed();
        $this->settings->set('auth_token', $response['access_token']);

        return $this;
    }

    /**
     *
     * @return Bridge_Api_Auth_OAuth2
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
        return $this->settings->get('auth_token') !== null;
    }

    /**
     *
     * @return Array
     */
    public function get_auth_signatures()
    {
        return [
            'auth_token' => $this->settings->get('auth_token')
        ];
    }

    /**
     *
     * @param  array                  $parameters
     * @return Bridge_Api_Auth_OAuth2
     */
    public function set_parameters(Array $parameters)
    {
        $avail_parameters = [
            'client_id'
            , 'client_secret'
            , 'redirect_uri'
            , 'scope'
            , 'response_type'
            , 'token_endpoint'
            , 'auth_endpoint'
        ];

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
    public function get_auth_url(Array $supp_parameters = [])
    {
        $params = array_merge([
            'response_type' => 'code',
            'client_id'     => $this->client_id,
            'redirect_uri'  => $this->redirect_uri,
            'scope'         => $this->scope
            ], $supp_parameters);

        return sprintf('%s?%s', $this->auth_endpoint, http_build_query($params, null, '&'));
    }
}
