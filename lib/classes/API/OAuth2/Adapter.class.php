<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @package     OAuth2 Connector
 *
 * @see         http://oauth.net/2/
 * @uses        http://code.google.com/p/oauth2-php/
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class API_OAuth2_Adapter extends OAuth2
{
    /**
     * Version
     */
    const API_VERSION = "1.0";

    /**
     *
     * @var API_OAuth2_Application
     */
    protected $client;

    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     * request parameter
     * @var array
     */
    protected $params;

    /**
     *
     * @var array
     */
    protected $token_type = array("bearer" => "Bearer");

    /**
     * @var array
     */
    protected $authentication_scheme = array("authorization", "uri", "body");

    /**
     *
     * do we enable expiration on  access_token
     * @param boolean
     */
    protected $enable_expire = false;

    /**
     *
     * @var string
     */
    protected $session_id;

    /**
     *
     * @var string
     */
    protected $usr_id_requested;

    /**
     * access token of current request
     * @var string
     */
    protected $token;

    /**
     *
     * @param appbox $appbox
     * @return API_OAuth2_Adapter
     */
    public function __construct(appbox $appbox)
    {
        parent::__construct();
        $this->params = array();
        $this->appbox = $appbox;

        return $this;
    }

    /**
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     *
     * @return API_OAuth2_Application
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     *
     * @param array $params
     * @return API_OAuth2_Adapter
     */
    public function setParams(array $params)
    {
        $this->params = $params;

        return $this;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     *
     * @param API_OAuth2_Application $client
     */
    public function setClient(API_OAuth2_Application $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function has_ses_id()
    {
        return $this->session_id !== null;
    }

    /**
     *
     * @return int
     */
    public function get_ses_id()
    {
        return $this->session_id;
    }

    /**
     *
     * @return int
     */
    public function get_usr_id()
    {
        return $this->usr_id;
    }

    /**
     *
     * @param string $secret
     * @param string $nonce
     * @return string
     */
    protected static function crypt_secret($secret, $nonce)
    {
        $registry = registry::get_instance();

        return hash_hmac('sha512', $secret . $nonce, $registry->get('GV_sit'));
    }

    /**
     *
     * Implements OAuth2::checkClientCredentials().
     *
     * @param string $client_id
     * @param string $client_secret
     * @return boolean
     */
    protected function checkClientCredentials($client_id, $client_secret = NULL)
    {
        try {
            $application = API_OAuth2_Application::load_from_client_id($this->appbox, $client_id);

            if ($client_secret === NULL)
                return true;

            $crypted = $this->crypt_secret($client_secret, $application->get_nonce());

            return ($result["client_secret"] === $crypted);
        } catch (Exception $e) {

        }

        return false;
    }

    /**
     *
     * Implements OAuth2::getRedirectUri().
     *
     * @param string $client_id
     * @return string
     */
    protected function getRedirectUri($client_id)
    {
        $application = API_OAuth2_Application::load_from_client_id($this->appbox, $client_id);

        return $application->get_redirect_uri();
    }

    /**
     *
     * Implements OAuth2::getAccessToken().
     *
     * @param string $oauth_token
     * @return array
     */
    protected function getAccessToken($oauth_token)
    {
        $result = null;
        try {
            $token = API_OAuth2_Token::load_by_oauth_token($this->appbox, $oauth_token);
            $result = array(
                'scope'       => $token->get_scope()
                , 'expires'     => $token->get_expires()
                , 'client_id'   => $token->get_account()->get_application()->get_client_id()
                , 'session_id'  => $token->get_session_id()
                , 'revoked'     => ($token->get_account()->is_revoked() ? '1' : '0')
                , 'usr_id'      => $token->get_account()->get_user()->get_id()
                , 'oauth_token' => $token->get_value()
            );
        } catch (Exception $e) {

        }

        return $result;
    }
    /**
     * Implements OAuth2::setAccessToken().
     */

    /**
     *
     * @param type $oauth_token
     * @param type $account_id
     * @param type $expires
     * @param string $scope
     * @return API_OAuth2_Adapter
     */
    protected function setAccessToken($oauth_token, $account_id, $expires, $scope = NULL)
    {
        $account = new API_OAuth2_Account($this->appbox, $account_id);
        $token = API_OAuth2_Token::create($this->appbox, $account, $scope);
        $token->set_value($oauth_token)->set_expires($expires);

        return $this;
    }

    /**
     *
     * Overrides OAuth2::getSupportedGrantTypes().
     *
     * @return array
     */
    protected function getSupportedGrantTypes()
    {
        return array(
            OAUTH2_GRANT_TYPE_AUTH_CODE,
            OAUTH2_GRANT_TYPE_USER_CREDENTIALS
        );
    }

    /**
     *
     * Overrides OAuth2::getSupportedScopes().
     *
     * @return array
     */
    protected function getSupportedScopes()
    {
        return array();
    }

    /**
     *
     * Overrides OAuth2::getAuthCode().
     *
     * @return array
     */
    protected function getAuthCode($code)
    {
        try {
            $code = new API_OAuth2_AuthCode($this->appbox, $code);

            return array(
                'redirect_uri' => $code->get_redirect_uri()
                , 'client_id'    => $code->get_account()->get_application()->get_client_id()
                , 'expires'      => $code->get_expires()
                , 'account_id'   => $code->get_account()->get_id()
            );
        } catch (Exception $e) {

        }

        return null;
    }

    /**
     *
     * Overrides OAuth2::setAuthCode().
     *
     * @param string $code
     * @param int $account_id
     * @param string $redirect_uri
     * @param string $expires
     * @param string $scope
     * @return API_OAuth2_Adapter
     */
    protected function setAuthCode($code, $account_id, $redirect_uri, $expires, $scope = NULL)
    {
        $account = new API_OAuth2_Account($this->appbox, $account_id);
        $code = API_OAuth2_AuthCode::create($this->appbox, $account, $code, $expires);
        $code->set_redirect_uri($redirect_uri)->set_scope($scope);

        return $this;
    }

    /**
     * Overrides OAuth2::setRefreshToken().
     */
    protected function setRefreshToken($refresh_token, $account_id, $expires, $scope = NULL)
    {
        $account = new API_OAuth2_Account($this->appbox, $account_id);
        API_OAuth2_RefreshToken::create($this->appbox, $account, $expires, $refresh_token, $scope);

        return $this;
    }

    /**
     * Overrides OAuth2::getRefreshToken().
     */
    protected function getRefreshToken($refresh_token)
    {
        try {
            $token = new API_OAuth2_RefreshToken($this->appbox, $refresh_token);

            return array(
                'token'     => $token->get_value()
                , 'expires'   => $token->get_expires()->format('U')
                , 'client_id' => $token->get_account()->get_application()->get_client_id()
            );
        } catch (Exception $e) {

        }

        return null;
    }

    /**
     * Overrides OAuth2::unsetRefreshToken().
     */
    protected function unsetRefreshToken($refresh_token)
    {
        $token = new API_OAuth2_RefreshToken($this->appbox, $refresh_token);
        $token->delete();

        return $this;
    }

    /**
     *
     * @param Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function getAuthorizationRequestParameters(Symfony\Component\HttpFoundation\Request $request)
    {

        $datas = array(
            'response_type' => $request->get('response_type', false)
            , 'client_id'     => $request->get('client_id', false)
            , 'redirect_uri'  => $request->get('redirect_uri', false)
        );


        $scope = $request->get('scope', false);
        $state = $request->get('state', false);

        if ($state) {
            $datas["state"] = $state;
        }

        if ($scope) {
            $datas["scope"] = $scope;
        }

        $filters = array(
            "client_id" => array(
                "filter"  => FILTER_VALIDATE_REGEXP
                , "options" => array("regexp"        => OAUTH2_CLIENT_ID_REGEXP)
                , "flags"         => FILTER_REQUIRE_SCALAR
            )
            , "response_type" => array(
                "filter"  => FILTER_VALIDATE_REGEXP
                , "options" => array("regexp"       => OAUTH2_AUTH_RESPONSE_TYPE_REGEXP)
                , "flags"        => FILTER_REQUIRE_SCALAR
            )
            , "redirect_uri" => array("filter" => FILTER_SANITIZE_URL)
            , "state"  => array("flags" => FILTER_REQUIRE_SCALAR)
            , "scope" => array("flags" => FILTER_REQUIRE_SCALAR)
        );


        $input = filter_var_array($datas, $filters);

        /**
         * check for valid client_id
         * check for valid redirect_uri
         */
        if ( ! $input["client_id"]) {
            if ($input["redirect_uri"])
                $this->errorDoRedirectUriCallback(
                    $input["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, NULL, NULL, $input["state"]
                );
            // We don't have a good URI to use
            $this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_CLIENT);
        }

        /**
         * redirect_uri is not required if already established via other channels
         * check an existing redirect URI against the one supplied
         */
        $redirect_uri = $this->getRedirectUri($input["client_id"]);

        /**
         *  At least one of: existing redirect URI or input redirect URI must be specified
         */
        if ( ! $redirect_uri && ! $input["redirect_uri"])
            $this->errorJsonResponse(
                OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_REQUEST);


        /**
         *  getRedirectUri() should return FALSE if the given client ID is invalid
         * this probably saves us from making a separate db call, and simplifies the method set
         */
        if ($redirect_uri === FALSE)
            $this->errorDoRedirectUriCallback(
                $input["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, NULL, NULL, $input["state"]);

        /**
         * If there's an existing uri and one from input, verify that they match
         */
        if ($redirect_uri && $input["redirect_uri"]) {
            /**
             *  Ensure that the input uri starts with the stored uri
             */
            $compare = strcasecmp(
                substr(
                    $input["redirect_uri"], 0, strlen($redirect_uri)
                ), $redirect_uri);
            if ($compare !== 0)
                $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_REDIRECT_URI_MISMATCH, NULL, NULL, $input["state"]);
        }
        elseif ($redirect_uri) {
            /**
             *  They did not provide a uri from input, so use the stored one
             */
            $input["redirect_uri"] = $redirect_uri;
        }


        /**
         * Check response_type
         */
        if ( ! $input["response_type"]) {
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_REQUEST, 'Invalid response type.', NULL, $input["state"]);
        }


        /**
         * Check requested auth response type against the list of supported types
         */
        if (array_search($input["response_type"], $this->getSupportedAuthResponseTypes()) === FALSE)
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE, NULL, NULL, $input["state"]);


        /**
         *  Restrict clients to certain authorization response types
         */
        if ($this->checkRestrictedAuthResponseType($input["client_id"], $input["response_type"]) === FALSE)
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_UNAUTHORIZED_CLIENT, NULL, NULL, $input["state"]);


        /**
         * Validate that the requested scope is supported
         */
        if ($input["scope"] && ! $this->checkScope($input["scope"], $this->getSupportedScopes()))
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_SCOPE, NULL, NULL, $input["state"]);

        /**
         * at this point all params are ok
         */
        $this->params = $input;

        return $input;
    }

    /**
     *
     * @param usr_id $usr_id
     * @return API_OAuth2_Account
     */
    public function updateAccount($usr_id)
    {
        if ($this->client === null)
            throw new logicalException("Client property must be set before update an account");

        try {
            $user = User_Adapter::getInstance($usr_id, $this->appbox);
            $account = API_OAuth2_Account::load_with_user($this->appbox, $this->client, $user);
        } catch (Exception $e) {
            $account = $this->createAccount($usr_id);
        }

        return $account;
    }

    /**
     *
     * @param int $usr_id
     * @return API_OAuth2_Account
     */
    private function createAccount($usr_id)
    {
        $user = User_Adapter::getInstance($usr_id, $this->appbox);

        return API_OAuth2_Account::create($this->appbox, $user, $this->client);
    }

    /**
     *
     * @param <type> $is_authorized
     * @param array $params
     * @return string
     */
    public function finishNativeClientAuthorization($is_authorized, $params = array())
    {
        $result = array();
        $params += array(
            'scope' => NULL,
            'state' => NULL,
        );
        extract($params);

        if ($state !== NULL)
            $result["query"]["state"] = $state;

        if ($is_authorized === FALSE) {
            $result["error"] = OAUTH2_ERROR_USER_DENIED;
        } else {
            if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE)
                $result["code"] = $this->createAuthCode($account_id, $redirect_uri, $scope);

            if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN)
                $result["error"] = OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE;
        }

        return $result;
    }

    /**
     *
     * @param <type> $redirect_uri
     * @return <type>
     */
    public function isNativeApp($redirect_uri)
    {
        return $redirect_uri === API_OAuth2_Application::NATIVE_APP_REDIRECT_URI;
    }

    public function remember_this_ses_id($ses_id)
    {
        try {
            $token = API_OAuth2_Token::load_by_oauth_token($this->appbox, $this->token);
            $token->set_session_id($ses_id);

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    public function verifyAccessToken($scope = NULL, $exit_not_present = TRUE, $exit_invalid = TRUE, $exit_expired = TRUE, $exit_scope = TRUE, $realm = NULL)
    {
        $token_param = $this->getAccessTokenParams();


        if ($token_param === FALSE) // Access token was not provided
            return $exit_not_present ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_BAD_REQUEST, $realm, OAUTH2_ERROR_INVALID_REQUEST, 'The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.', NULL, $scope) : FALSE;
        // Get the stored token data (from the implementing subclass)
        $token = $this->getAccessToken($token_param);

        if ($token === NULL)
            return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'The access token provided is invalid.', NULL, $scope) : FALSE;

        if (isset($token['revoked']) && $token['revoked']) {
            return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'End user has revoked access to his personal datas for your application.', NULL, $scope) : FALSE;
        }

        if ($this->enable_expire) {
            // Check token expiration (I'm leaving this check separated, later we'll fill in better error messages)
            if (isset($token["expires"]) && time() > $token["expires"])
                return $exit_expired ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_EXPIRED_TOKEN, 'The access token provided has expired.', NULL, $scope) : FALSE;
        }
        // Check scope, if provided
        // If token doesn't have a scope, it's NULL/empty, or it's insufficient, then throw an error
        if ($scope && ( ! isset($token["scope"]) || ! $token["scope"] || ! $this->checkScope($scope, $token["scope"])))
            return $exit_scope ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_FORBIDDEN, $realm, OAUTH2_ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', NULL, $scope) : FALSE;

        //save token's linked ses_id
        $this->session_id = $token['session_id'];
        $this->usr_id = $token['usr_id'];
        $this->token = $token['oauth_token'];

        return TRUE;
    }

    public function finishClientAuthorization($is_authorized, $params = array())
    {
        $params += array(
            'scope' => NULL,
            'state' => NULL,
        );
        extract($params);

        if ($state !== NULL)
            $result["query"]["state"] = $state;
        if ($is_authorized === FALSE)
            $result["query"]["error"] = OAUTH2_ERROR_USER_DENIED;
        else {
            if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
                $result["query"]["code"] = $this->createAuthCode($account_id, $redirect_uri, $scope);

            if ($response_type == OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN || $response_type == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN)
                $result["fragment"] = $this->createAccessToken($account_id, $scope);
        }
        $this->doRedirectUriCallback($redirect_uri, $result);
    }

    /**
     *
     */
    public function grantAccessToken()
    {
        $filters = array(
            "grant_type" => array("filter"  => FILTER_VALIDATE_REGEXP, "options" => array("regexp" => OAUTH2_GRANT_TYPE_REGEXP), "flags"  => FILTER_REQUIRE_SCALAR),
            "scope"  => array("flags" => FILTER_REQUIRE_SCALAR),
            "code"  => array("flags"        => FILTER_REQUIRE_SCALAR),
            "redirect_uri" => array("filter"   => FILTER_SANITIZE_URL),
            "username" => array("flags"    => FILTER_REQUIRE_SCALAR),
            "password" => array("flags"          => FILTER_REQUIRE_SCALAR),
            "assertion_type" => array("flags"     => FILTER_REQUIRE_SCALAR),
            "assertion" => array("flags"         => FILTER_REQUIRE_SCALAR),
            "refresh_token" => array("flags" => FILTER_REQUIRE_SCALAR),
        );

        $input = filter_input_array(INPUT_POST, $filters);

        // Grant Type must be specified.
        if ( ! $input["grant_type"])
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');

        // Make sure we've implemented the requested grant type
        if ( ! in_array($input["grant_type"], $this->getSupportedGrantTypes()))
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);

        // Authorize the client
        $client = $this->getClientCredentials();

        if ($this->checkClientCredentials($client[0], $client[1]) === FALSE)
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);

        if ( ! $this->checkRestrictedGrantType($client[0], $input["grant_type"]))
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);

        // Do the granting
        switch ($input["grant_type"]) {
            case OAUTH2_GRANT_TYPE_AUTH_CODE:
                if ( ! $input["code"] || ! $input["redirect_uri"])
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
                $stored = $this->getAuthCode($input["code"]);

                // Ensure that the input uri starts with the stored uri
                if ($stored === NULL || (strcasecmp(substr($input["redirect_uri"], 0, strlen($stored["redirect_uri"])), $stored["redirect_uri"]) !== 0) || $client[0] != $stored["client_id"])
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

                if ($stored["expires"] < time())
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);
                break;
            case OAUTH2_GRANT_TYPE_USER_CREDENTIALS:
                if ( ! $input["username"] || ! $input["password"])
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Missing parameters. "username" and "password" required');

                $stored = $this->checkUserCredentials($client[0], $input["username"], $input["password"]);

                if ($stored === FALSE)
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT, 'Unknow user');

                break;
            case OAUTH2_GRANT_TYPE_ASSERTION:
                if ( ! $input["assertion_type"] || ! $input["assertion"])
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);

                $stored = $this->checkAssertion($client[0], $input["assertion_type"], $input["assertion"]);

                if ($stored === FALSE)
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

                break;
            case OAUTH2_GRANT_TYPE_REFRESH_TOKEN:
                if ( ! $input["refresh_token"])
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');

                $stored = $this->getRefreshToken($input["refresh_token"]);

                if ($stored === NULL || $client[0] != $stored["client_id"])
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);

                if ($stored["expires"] < time())
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);

                // store the refresh token locally so we can delete it when a new refresh token is generated
                $this->setVariable('_old_refresh_token', $stored["token"]);

                break;
            case OAUTH2_GRANT_TYPE_NONE:
                $stored = $this->checkNoneAccess($client[0]);

                if ($stored === FALSE)
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
        }

        // Check scope, if provided
        if ($input["scope"] && ( ! is_array($stored) || ! isset($stored["scope"]) || ! $this->checkScope($input["scope"], $stored["scope"])))
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_SCOPE);

        if ( ! $input["scope"])
            $input["scope"] = NULL;

        $token = $this->createAccessToken($stored['account_id'], $input["scope"]);
        $this->sendJsonHeaders();

        echo json_encode($token);

        return;
    }

    protected function createAccessToken($account_id, $scope = NULL)
    {
        $token = array(
            "access_token" => $this->genAccessToken(),
            "scope"        => $scope
        );

        if ($this->enable_expire)
            $token['expires_in'] = $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME);

        $this->setAccessToken($token["access_token"], $account_id, time() + $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME), $scope);

        // Issue a refresh token also, if we support them
        if (in_array(OAUTH2_GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes())) {
            $token["refresh_token"] = $this->genAccessToken();
            $this->setRefreshToken($token["refresh_token"], $account_id, time() + $this->getVariable('refresh_token_lifetime', OAUTH2_DEFAULT_REFRESH_TOKEN_LIFETIME), $scope);
            // If we've granted a new refresh token, expire the old one
            if ($this->getVariable('_old_refresh_token'))
                $this->unsetRefreshToken($this->getVariable('_old_refresh_token'));
        }

        return $token;
    }

    protected function checkUserCredentials($client_id, $username, $password)
    {
        try {
            $appbox = appbox::get_instance(\bootstrap::getCore());

            $application = API_OAuth2_Application::load_from_client_id($appbox, $client_id);

            $auth = new \Session_Authentication_Native($appbox, $username, $password);

            $auth->challenge_password();

            $account = API_OAuth2_Account::load_with_user($appbox, $application, $auth->get_user());

            return array(
                'redirect_uri' => $application->get_redirect_uri()
                , 'client_id'    => $application->get_client_id()
                , 'account_id'   => $account->get_id()
            );
        } catch (\Exception $e) {
            return false;
        }
    }
}
