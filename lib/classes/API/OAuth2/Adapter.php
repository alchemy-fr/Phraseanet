<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\ControllerProvider\Api\V2;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\ApiApplicationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class API_OAuth2_Adapter extends OAuth2
{
    /**
     * @var ApiApplication
     */
    protected $client;

    /**
     * @var Application
     */
    protected $app;

    /**
     * @var array
     */
    protected $params;

    /**
     * @var array
     */
    protected $token_type = ["bearer" => "Bearer"];

    /**
     * @var array
     */
    protected $authentication_scheme = ["authorization", "uri", "body"];

    /**
     * do we enable expiration on  access_token
     * @param bool
     */
    protected $enable_expire = false;

    /**
     * @var string
     */
    protected $session_id;

    /**
     * access token of current request
     * @var string
     */
    protected $token;

    /**
     * @param  Application $app
     * @param array $conf
     */
    public function __construct(Application $app, array $conf = [])
    {
        parent::__construct($conf);
        $this->app = $app;
        $this->params = [];
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return ApiApplication
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param array $params
     * @return $this
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

    public function setClient(ApiApplication $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return bool
     */
    public function has_ses_id()
    {
        return $this->session_id !== null;
    }

    /**
     * @return int
     */
    public function get_ses_id()
    {
        return $this->session_id;
    }

    /**
     * Implements OAuth2::checkClientCredentials().
     *
     * @param  string  $clientId
     * @param  string  $clientSecret
     * @return bool
     */
    protected function checkClientCredentials($clientId, $clientSecret = null)
    {
        if (null === $application = $this->app['repo.api-applications']->findByClientId($clientId)) {
            return false;
        }

        if (null === $clientSecret) {
            return true;
        }

        return $application->getClientSecret() === $clientSecret;
    }

    /**
     * Implements OAuth2::getRedirectUri().
     *
     * @param string $clientId
     *
     * @return string
     * @throws RuntimeException
     */
    protected function getRedirectUri($clientId)
    {
        if (null === $application = $this->app['repo.api-applications']->findByClientId($clientId)) {
            throw new BadRequestHttpException(sprintf('Application with client id %s could not be found', $clientId));
        }

        return $application->getRedirectUri();
    }

    /**
     * Implements OAuth2::getAccessToken().
     *
     * @param  string $oauthToken
     * @return array
     */
    protected function getAccessToken($oauthToken)
    {
        if (null === $token = $this->app['repo.api-oauth-tokens']->find($oauthToken)) {
            return null;
        }

        return [
            'scope'       => $token->getScope(),
            'expires'     => $token->getExpires(),
            'client_id'   => $token->getAccount()->getApplication()->getClientId(),
            'session_id'  => $token->getSessionId(),
            'revoked'     => (int) $token->getAccount()->isRevoked(),
            'usr_id'      => $token->getAccount()->getUser()->getId(),
            'oauth_token' => $token->getOauthToken(),
        ];
    }
    /**
     * Implements OAuth2::setAccessToken().
     *
     * @param      $oauthToken
     * @param      $accountId
     * @param      $expires
     * @param null $scope
     *
     * @return $this
     * @throws RuntimeException
     */
    protected function setAccessToken($oauthToken, $accountId, $expires = null, $scope = null)
    {
        if (null === $account = $this->app['repo.api-accounts']->find($accountId)) {
            throw new RuntimeException(sprintf('Account with id %s is not valid', $accountId));
        }
        $token = $this->app['manipulator.api-oauth-token']->create($account, $expires, $scope);
        $this->app['manipulator.api-oauth-token']->setOauthToken($token, $oauthToken);

        return $this;
    }

    /**
     * Overrides OAuth2::getSupportedGrantTypes().
     *
     * @return array
     */
    protected function getSupportedGrantTypes()
    {
        return [
            OAUTH2_GRANT_TYPE_AUTH_CODE,
            OAUTH2_GRANT_TYPE_USER_CREDENTIALS
        ];
    }

    /**
     * Overrides OAuth2::getSupportedScopes().
     *
     * @return array
     */
    protected function getSupportedScopes()
    {
        return [];
    }

    /**
     * Overrides OAuth2::getAuthCode().
     *
     * @param $code
     *
     * @return array|null
     */
    protected function getAuthCode($code)
    {
        if (null === $code = $this->app['repo.api-oauth-codes']->find($code)) {
            return null;
        }

        return [
            'redirect_uri' => $code->getRedirectUri(),
            'client_id'    => $code->getAccount()->getApplication()->getClientId(),
            'expires'      => $code->getExpires(),
            'account_id'   => $code->getAccount()->getId(),
        ];
    }

    /**
     * Overrides OAuth2::setAuthCode().
     *
     * @param      $oauthCode
     * @param      $accountId
     * @param      $redirectUri
     * @param      $expires
     * @param null $scope
     *
     * @return $this|void
     * @throws RuntimeException
     */
    protected function setAuthCode($oauthCode, $accountId, $redirectUri, $expires = null, $scope = null)
    {
        if (null === $account = $this->app['repo.api-accounts']->find($accountId)) {
            throw new RuntimeException(sprintf('Account with id %s is not valid', $accountId));
        }
        $code = $this->app['manipulator.api-oauth-code']->create($account, $redirectUri, $expires, $scope);
        $this->app['manipulator.api-oauth-code']->setCode($code, $oauthCode);

        return $this;
    }

    /**
     * Overrides OAuth2::setRefreshToken().
     *
     * @param      $refreshToken
     * @param      $accountId
     * @param      $expires
     * @param null $scope
     *
     * @return $this|void
     * @throws RuntimeException
     */
    protected function setRefreshToken($refreshToken, $accountId, $expires, $scope = null)
    {
        if (null === $account = $this->app['repo.api-accounts']->find($accountId)) {
            throw new RuntimeException(sprintf('Account with id %s is not valid', $accountId));
        }
        $token = $this->app['manipulator.api-oauth-refresh-token']->create($account, $expires, $scope);
        $this->app['manipulator.api-oauth-refresh-token']->setRefreshToken($token, $refreshToken);

        return $this;
    }

    /**
     * Overrides OAuth2::getRefreshToken().
     *
     * @param $refreshToken
     *
     * @return array|null
     */
    protected function getRefreshToken($refreshToken)
    {
        if (null === $token = $this->app['repo.api-oauth-refresh-token']->find($refreshToken)) {
            return null;
        }

        return [
            'token'     => $token->getRefreshToken(),
            'expires'   => $token->getExpires(),
            'client_id' => $token->getAccount()->getApplication()->getClientId()
        ];
    }

    /**
     * Overrides OAuth2::unsetRefreshToken().
     *
     * @param $refreshToken
     *
     * @return $this|void
     */
    protected function unsetRefreshToken($refreshToken)
    {
        if (null !== $token = $this->app['repo.api-oauth-refresh-token']->find($refreshToken)) {
            $this->app['manipulator.api-oauth-refresh-token']->delete($token);
        }

        return $this;
    }


    private function getCustomOrRealParm(Request $request, array $customParms, string $parmName)
    {
        if(array_key_exists($parmName, $customParms)) {
            return $customParms[$parmName];
        }
        return $request->get($parmName, false);
    }

    /**
     * @param Request $request
     * @param array $customParms
     * @return array
     */
    public function getAuthorizationRequestParameters(Request $request, $customParms = [])
    {
        $data = [
            'response_type' => $this->getCustomOrRealParm($request, $customParms, 'response_type'),
            'client_id' => $this->getCustomOrRealParm($request, $customParms, 'client_id'),
            'redirect_uri' => $this->getCustomOrRealParm($request, $customParms, 'redirect_uri'),
        ];

        $scope = $this->getCustomOrRealParm($request, $customParms, 'scope');
        $state = $this->getCustomOrRealParm($request, $customParms, 'state');

        if ($state) {
            $data["state"] = $state;
        }

        if ($scope) {
            $data["scope"] = $scope;
        }

        $filters = [
            "client_id" => [
                "filter"  => FILTER_VALIDATE_REGEXP
                , "options" => ["regexp"        => OAUTH2_CLIENT_ID_REGEXP]
                , "flags"         => FILTER_REQUIRE_SCALAR
            ]
            , "response_type" => [
                "filter"  => FILTER_VALIDATE_REGEXP
                , "options" => ["regexp"       => OAUTH2_AUTH_RESPONSE_TYPE_REGEXP]
                , "flags"        => FILTER_REQUIRE_SCALAR
            ]
            , "redirect_uri" => ["filter" => FILTER_SANITIZE_URL]
            , "state"  => ["flags" => FILTER_REQUIRE_SCALAR]
            , "scope" => ["flags" => FILTER_REQUIRE_SCALAR]
        ];

        $input = filter_var_array($data, $filters);

        /**
         * check for valid client_id
         * check for valid redirect_uri
         */
        if (! $input["client_id"]) {
            if ($input["redirect_uri"]) {
                $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, null, null, $input["state"]);
            }
            // We don't have a good URI to use
            $this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_CLIENT);
        }

        /**
         * redirect_uri is not required if already established via other channels
         * check an existing redirect URI against the one supplied
         */
        $redirectUri = $this->getRedirectUri($input["client_id"]);

        /**
         *  At least one of: existing redirect URI or input redirect URI must be specified
         */
        if (! $redirectUri && ! $input["redirect_uri"]) {
            $this->errorJsonResponse(OAUTH2_HTTP_FOUND, OAUTH2_ERROR_INVALID_REQUEST);
        }

        /**
         *  getRedirectUri() should return false if the given client ID is invalid
         * this probably saves us from making a separate db call, and simplifies the method set
         */
        if ($redirectUri === false) {
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_CLIENT, null, null, $input["state"]);
        }

        /**
         * If there's an existing uri and one from input, verify that they match
         */
        if ($redirectUri && $input["redirect_uri"]) {
            /**
             *  Ensure that the input uri starts with the stored uri
             */
            $compare = strcasecmp(
                substr(
                    $input["redirect_uri"], 0, strlen($redirectUri)
                ), $redirectUri);
            if ($compare !== 0) {
                $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_REDIRECT_URI_MISMATCH, null, null, $input["state"]);
            }
        } elseif ($redirectUri) {
            /**
             *  They did not provide a uri from input, so use the stored one
             */
            $input["redirect_uri"] = $redirectUri;
        }

        /**
         * Check response_type
         */
        if (! $input["response_type"]) {
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_REQUEST, 'Invalid response type.', null, $input["state"]);
        }

        /**
         * Check requested auth response type against the list of supported types
         */
        if (array_search($input["response_type"], $this->getSupportedAuthResponseTypes()) === false) {
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE, null, null, $input["state"]);
        }

        /**
         *  Restrict clients to certain authorization response types
         */
        if ($this->checkRestrictedAuthResponseType($input["client_id"], $input["response_type"]) === false) {
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_UNAUTHORIZED_CLIENT, null, null, $input["state"]);
        }

        /**
         * Validate that the requested scope is supported
         */
        if ($input["scope"] && ! $this->checkScope($input["scope"], $this->getSupportedScopes())) {
            $this->errorDoRedirectUriCallback($input["redirect_uri"], OAUTH2_ERROR_INVALID_SCOPE, null, null, $input["state"]);
        }

        /**
         * at this point all params are ok
         */
        $this->params = $input;

        return $input;
    }

    /**
     * @param User $user
     *
     * @return mixed
     * @throws LogicException
     */
    public function updateAccount(User $user)
    {
        if ($this->client === null) {
            throw new LogicException("Client property must be set before update an account");
        }

        if (null === $account = $this->app['repo.api-accounts']->findByUserAndApplication($user, $this->client)) {
            $account = $this->app['manipulator.api-account']->create($this->client, $user, $this->getVariable('api_version', V2::VERSION));
        }

        return $account;
    }

    /**
     * @param       $is_authorized
     * @param array $params
     *
     * @return array
     */
    public function finishNativeClientAuthorization($is_authorized, $params = [])
    {
        $result = [];
        $params += ['scope' => null, 'state' => null,];

        if ($params['state'] !== null) {
            $result["query"]["state"] = $params['state'] ;
        }

        if ($is_authorized === false) {
            $result["error"] = OAUTH2_ERROR_USER_DENIED;
        } else {
            if ($params['response_type'] === OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE) {
                $result["code"] = $this->createAuthCode($params['account_id'], $params['redirect_uri'], $params['scope']);
            }

            if ($params['response_type'] === OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN) {
                $result["error"] = OAUTH2_ERROR_UNSUPPORTED_RESPONSE_TYPE;
            }
        }

        return $result;
    }

    /**
     * @param $redirectUri
     *
     * @return bool
     */
    public function isNativeApp($redirectUri)
    {
        return $redirectUri === ApiApplication::NATIVE_APP_REDIRECT_URI;
    }

    public function rememberSession(Session $session)
    {
        if (null !== $token = $this->app['repo.api-oauth-tokens']->find($this->token)) {
            $this->app['manipulator.api-oauth-token']->rememberSessionId($token, $session->getId());
        }
    }

    public function verifyAccessToken($scope = null, $exit_not_present = true, $exit_invalid = true, $exit_expired = true, $exit_scope = true, $realm = null)
    {
        $apiTokenHeader = $this->app['conf']->get(['registry', 'api-clients', 'api-auth-token-header-only']);

        $useTokenHeader = $this->useTokenHeaderChoice($apiTokenHeader);

        $token_param = $this->getAccessTokenParams($useTokenHeader);

        // Access token was not provided
        if ($token_param === false) {
            return $exit_not_present ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_BAD_REQUEST, $realm, OAUTH2_ERROR_INVALID_REQUEST, 'The request is missing a required parameter, includes an unsupported parameter or parameter value, repeats the same parameter, uses more than one method for including an access token, or is otherwise malformed.', null, $scope) : false;
        }

        // Get the stored token data (from the implementing subclass)
        $token = $this->getAccessToken($token_param);

        if ($token === null) {
            return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'The access token provided is invalid.', null, $scope) : false;
        }

        if (isset($token['revoked']) && $token['revoked']) {
            return $exit_invalid ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_INVALID_TOKEN, 'End user has revoked access to his personal datas for your application.', null, $scope) : false;
        }

        if ($this->enable_expire) {
            // Check token expiration (I'm leaving this check separated, later we'll fill in better error messages)
            if (isset($token["expires"]) && time() > $token["expires"]) {
                return $exit_expired ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_UNAUTHORIZED, $realm, OAUTH2_ERROR_EXPIRED_TOKEN, 'The access token provided has expired.', null, $scope) : false;
            }
        }
        // Check scope, if provided
        // If token doesn't have a scope, it's null/empty, or it's insufficient, then throw an error
        if ($scope && ( ! isset($token["scope"]) || ! $token["scope"] || ! $this->checkScope($scope, $token["scope"]))) {
            return $exit_scope ? $this->errorWWWAuthenticateResponseHeader(OAUTH2_HTTP_FORBIDDEN, $realm, OAUTH2_ERROR_INSUFFICIENT_SCOPE, 'The request requires higher privileges than provided by the access token.', null, $scope) : false;
        }
        //save token's linked ses_id
        $this->session_id = $token['session_id'];
        $this->token = $token['oauth_token'];

        return true;
    }

    public function finishClientAuthorization($is_authorized, $params = [])
    {
        $params += [
            'scope' => null,
            'state' => null,
        ];

        $result = [];

        if ($params['state'] !== null) {
            $result["query"]["state"] = $params['state'];
        }

        if ($is_authorized === false) {
            $result["query"]["error"] = OAUTH2_ERROR_USER_DENIED;
        } else {
            if ($params['response_type'] == OAUTH2_AUTH_RESPONSE_TYPE_AUTH_CODE || $params['response_type'] == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN) {
                $result["query"]["code"] = $this->createAuthCode($params['account_id'], $params['redirect_uri'], $params['scope']);
            }

            if ($params['response_type'] == OAUTH2_AUTH_RESPONSE_TYPE_ACCESS_TOKEN || $params['response_type'] == OAUTH2_AUTH_RESPONSE_TYPE_CODE_AND_TOKEN) {
                $result["fragment"] = $this->createAccessToken($params['account_id'], $params['scope']);
            }
        }

        $this->doRedirectUriCallback($params['redirect_uri'], $result);
    }

    public function grantAccessToken()
    {
        $filters = [
            "grant_type" => [
                "filter" => FILTER_VALIDATE_REGEXP,
                "options" => ["regexp" => OAUTH2_GRANT_TYPE_REGEXP],
                "flags" => FILTER_REQUIRE_SCALAR
            ],
            "scope" => ["flags" => FILTER_REQUIRE_SCALAR],
            "code" => ["flags" => FILTER_REQUIRE_SCALAR],
            "redirect_uri" => ["filter" => FILTER_SANITIZE_URL],
            "username" => ["flags" => FILTER_REQUIRE_SCALAR],
            "password" => ["flags" => FILTER_REQUIRE_SCALAR],
            "assertion_type" => ["flags" => FILTER_REQUIRE_SCALAR],
            "assertion" => ["flags" => FILTER_REQUIRE_SCALAR],
            "refresh_token" => ["flags" => FILTER_REQUIRE_SCALAR],
        ];

        $input = filter_input_array(INPUT_POST, $filters);

        // Grant Type must be specified.
        if (! $input["grant_type"]) {
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Invalid grant_type parameter or parameter missing');
        }

        // Make sure we've implemented the requested grant type
        if ( ! in_array($input["grant_type"], $this->getSupportedGrantTypes())) {
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE);
        }

        // Authorize the client
        $client = $this->getClientCredentials();

        if ($this->checkClientCredentials($client[0], $client[1]) === false) {
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_CLIENT);
        }

        if ( ! $this->checkRestrictedGrantType($client[0], $input["grant_type"])) {
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);
        }

        if ( ! $this->checkRestrictedGrantType($client[0], $input["grant_type"])) {
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNAUTHORIZED_CLIENT);
        }

        // Do the granting
        switch ($input["grant_type"]) {
            case OAUTH2_GRANT_TYPE_AUTH_CODE:
                if (! $input["code"] || ! $input["redirect_uri"]) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
                }
                $stored = $this->getAuthCode($input["code"]);

                // Ensure that the input uri starts with the stored uri
                if ($stored === null || (strcasecmp(substr($input["redirect_uri"], 0, strlen($stored["redirect_uri"])), $stored["redirect_uri"]) !== 0) || $client[0] != $stored["client_id"]) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);
                }

                if ($stored["expires"] < time()) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);
                }
                break;
            case OAUTH2_GRANT_TYPE_USER_CREDENTIALS:
                /** @var ApiApplicationRepository $appRepository */
                $appRepository = $this->app['repo.api-applications'];
                $application = $appRepository->findByClientId($client[0]);

                if (! $application) {
                    throw new NotFoundHttpException('Application not found');
                }

                if ( ! $application->isPasswordGranted()) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_UNSUPPORTED_GRANT_TYPE, 'Password grant type is not enable for your client');
                }

                if (! $input["username"] || ! $input["password"]) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'Missing parameters. "username" and "password" required');
                }

                $stored = $this->checkUserCredentials($client[0], $input["username"], $input["password"]);

                if ($stored === false) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT, 'Username/password mismatch or account locked, please try to log in via Web Application');
                }
                break;
            case OAUTH2_GRANT_TYPE_ASSERTION:
                if (! $input["assertion_type"] || ! $input["assertion"]) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
                }

                $stored = $this->checkAssertion($client[0], $input["assertion_type"], $input["assertion"]);

                if ($stored === false) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);
                }

                break;
            case OAUTH2_GRANT_TYPE_REFRESH_TOKEN:
                if (! $input["refresh_token"]) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST, 'No "refresh_token" parameter found');
                }

                $stored = $this->getRefreshToken($input["refresh_token"]);

                if ($stored === null || $client[0] != $stored["client_id"]) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_GRANT);
                }

                if ($stored["expires"] < time()) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_EXPIRED_TOKEN);
                }

                // store the refresh token locally so we can delete it when a new refresh token is generated
                $this->setVariable('_old_refresh_token', $stored["token"]);

                break;
            case OAUTH2_GRANT_TYPE_NONE:
                $stored = $this->checkNoneAccess($client[0]);

                if ($stored === false) {
                    $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_REQUEST);
                }
        }

        // Check scope, if provided
        if ($input["scope"] && ( ! is_array($stored) || ! isset($stored["scope"]) || ! $this->checkScope($input["scope"], $stored["scope"]))) {
            $this->errorJsonResponse(OAUTH2_HTTP_BAD_REQUEST, OAUTH2_ERROR_INVALID_SCOPE);
        }

        if (! $input["scope"]) {
            $input["scope"] = null;
        }

        $token = $this->createAccessToken($stored['account_id'], $input["scope"]);
        $this->sendJsonHeaders();

        echo json_encode($token);

        return;
    }

    protected function createAccessToken($accountId, $scope = null)
    {
        $token = [
            "access_token" => $this->genAccessToken(),
            "scope"        => $scope
        ];

        if ($this->enable_expire) {
            $token['expires_in'] = $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME);
        }

        $this->setAccessToken($token["access_token"], $accountId, time() + $this->getVariable('access_token_lifetime', OAUTH2_DEFAULT_ACCESS_TOKEN_LIFETIME), $scope);

        // Issue a refresh token also, if we support them
        if (in_array(OAUTH2_GRANT_TYPE_REFRESH_TOKEN, $this->getSupportedGrantTypes())) {
            $token["refresh_token"] = $this->genAccessToken();
            $this->setRefreshToken($token["refresh_token"], $accountId, time() + $this->getVariable('refresh_token_lifetime', OAUTH2_DEFAULT_REFRESH_TOKEN_LIFETIME), $scope);
            // If we've granted a new refresh token, expire the old one
            if ($this->getVariable('_old_refresh_token')) {
                $this->unsetRefreshToken($this->getVariable('_old_refresh_token'));
            }
        }

        return $token;
    }

    /**
     * @param $clientId
     * @param $username
     * @param $password
     *
     * @return array|boolean
     */
    protected function checkUserCredentials($clientId, $username, $password)
    {
        try {
            if (null === $client = $this->app['repo.api-applications']->findByClientId($clientId)) {
                return false;
            }
            $this->setClient($client);

            $usrId = $this->app['auth.native']->getUsrId($username, $password, Request::createFromGlobals());

            if (!$usrId) {
                return false;
            }

            if (null === $user = $this->app['repo.users']->find($usrId)) {
                return false;
            }

            $account = $this->updateAccount($user);

            return [
                'redirect_uri' => $this->client->getRedirectUri(),
                'client_id'    => $this->client->getClientId(),
                'account_id'   => $account->getId(),
            ];
        } catch (AccountLockedException $e) {
            return false;
        } catch (RequireCaptchaException $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get the correct constante to call on Oauth2
     *
     * @param $apiTokenHeaderOnly
     * @return string
     */
    private function useTokenHeaderChoice($apiTokenHeaderOnly)
    {
        if ($apiTokenHeaderOnly === true) {
            return Oauth2::TOKEN_ONLY_IN_HEADER;
        } else {
            return Oauth2::TOKEN_AUTO_FIND;
        }
    }
}
