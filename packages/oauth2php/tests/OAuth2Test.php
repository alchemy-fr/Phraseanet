<?php

use PHPUnit\Framework\TestCase;

class OAuth2Test extends TestCase
{
    protected function tearDown(): void
    {
        $_GET = $_POST = array();
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
    }

    public function testVariables()
    {
        $oauth = new OAuth2Stub(array('display_error' => TRUE));

        $this->assertTrue($oauth->getVariable('display_error'));
        $this->assertSame('default', $oauth->getVariable('unknown', 'default'));
        $this->assertSame($oauth, $oauth->setVariable('foo', 'bar'));
        $this->assertSame('bar', $oauth->getVariable('foo'));
    }

    public function testCheckScope()
    {
        $oauth = new OAuth2Stub();

        $this->assertTrue($oauth->callCheckScope('read', 'read write'));
        $this->assertTrue($oauth->callCheckScope(array('read', 'write'), array('read', 'write')));
        $this->assertFalse($oauth->callCheckScope('read admin', 'read write'));
        $this->assertFalse($oauth->callCheckScope('read', NULL));
    }

    public function testBuildUri()
    {
        $oauth = new OAuth2Stub();

        $this->assertSame(
            'https://example.com/callback?foo=bar&code=1234&state=xyz#access_token=abcd',
            $oauth->callBuildUri('https://example.com/callback?foo=bar', array(
                'query' => array('code' => '1234', 'state' => 'xyz'),
                'fragment' => array('access_token' => 'abcd'),
            ))
        );
    }

    public function testAccessTokenFromAuthorizationHeader()
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'OAuth  my-token ';
        $oauth = new OAuth2Stub();

        $this->assertSame('my-token', $oauth->callGetAccessTokenParams());
    }

    public function testAccessTokenFromQuery()
    {
        $_GET[OAUTH2_TOKEN_PARAM_NAME] = 'query-token';
        $oauth = new OAuth2Stub();

        $this->assertSame('query-token', $oauth->callGetAccessTokenParams());
        $this->assertFalse($oauth->callGetAccessTokenParams(OAuth2::TOKEN_ONLY_IN_HEADER));
    }

    public function testAccessTokenFromPost()
    {
        $_POST[OAUTH2_TOKEN_PARAM_NAME] = 'post-token';
        $oauth = new OAuth2Stub();

        $this->assertSame('post-token', $oauth->callGetAccessTokenParams());
    }

    public function testMissingAccessToken()
    {
        $oauth = new OAuth2Stub();

        $this->assertFalse($oauth->callGetAccessTokenParams());
        $this->assertFalse($oauth->verifyAccessToken(NULL, FALSE));
    }

    public function testVerifyAccessToken()
    {
        $oauth = new OAuth2Stub();

        $_GET[OAUTH2_TOKEN_PARAM_NAME] = 'valid';
        $this->assertTrue($oauth->verifyAccessToken());
        $this->assertTrue($oauth->verifyAccessToken('read'));
        $this->assertFalse($oauth->verifyAccessToken('admin', TRUE, TRUE, TRUE, FALSE));

        $_GET[OAUTH2_TOKEN_PARAM_NAME] = 'expired';
        $this->assertFalse($oauth->verifyAccessToken(NULL, TRUE, TRUE, FALSE));

        $_GET[OAUTH2_TOKEN_PARAM_NAME] = 'unknown';
        $this->assertFalse($oauth->verifyAccessToken(NULL, TRUE, FALSE));
    }

    public function testClientCredentials()
    {
        $oauth = new OAuth2Stub();

        $_SERVER['PHP_AUTH_USER'] = 'client';
        $this->assertSame(array('client', NULL), $oauth->callGetClientCredentials());

        $_SERVER['PHP_AUTH_PW'] = 'secret';
        $this->assertSame(array('client', 'secret'), $oauth->callGetClientCredentials());

        unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        $_POST = array('client_id' => 'post-client', 'client_secret' => 'post-secret');
        $this->assertSame(array('post-client', 'post-secret'), $oauth->callGetClientCredentials());
    }

    public function testCreateAccessTokenWithRefreshToken()
    {
        $oauth = new OAuth2Stub(array('access_token_lifetime' => 60));
        $token = $oauth->callCreateAccessToken('client', 'read');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token['access_token']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token['refresh_token']);
        $this->assertSame(60, $token['expires_in']);
        $this->assertSame('read', $token['scope']);
        $this->assertSame(array('client', 'read'), array_slice($oauth->storedAccessTokens[$token['access_token']], 0, 2));
        $this->assertArrayHasKey($token['refresh_token'], $oauth->storedRefreshTokens);
    }

    public function testCreateAuthCode()
    {
        $oauth = new OAuth2Stub();
        $code = $oauth->callCreateAuthCode('client', 'https://example.com/callback', 'read');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $code);
        $this->assertSame(array('client', 'https://example.com/callback', 'read'), array_slice($oauth->storedAuthCodes[$code], 0, 3));
    }
}

class OAuth2Stub extends OAuth2
{
    public $storedAccessTokens = array();
    public $storedRefreshTokens = array();
    public $storedAuthCodes = array();

    public function callCheckScope($required, $available)
    {
        return $this->checkScope($required, $available);
    }

    public function callBuildUri($uri, $params)
    {
        return $this->buildUri($uri, $params);
    }

    public function callGetAccessTokenParams($useTokenHeader = self::TOKEN_AUTO_FIND)
    {
        return $this->getAccessTokenParams($useTokenHeader);
    }

    public function callGetClientCredentials()
    {
        return $this->getClientCredentials();
    }

    public function callCreateAccessToken($clientId, $scope = NULL)
    {
        return $this->createAccessToken($clientId, $scope);
    }

    public function callCreateAuthCode($clientId, $redirectUri, $scope = NULL)
    {
        return $this->createAuthCode($clientId, $redirectUri, $scope);
    }

    protected function checkClientCredentials($client_id, $client_secret = NULL)
    {
        return 'client' === $client_id;
    }

    protected function getRedirectUri($client_id)
    {
        return 'https://example.com/callback';
    }

    protected function getAccessToken($oauth_token)
    {
        switch ($oauth_token) {
            case 'valid':
                return array('client_id' => 'client', 'expires' => time() + 60, 'scope' => 'read write');
            case 'expired':
                return array('client_id' => 'client', 'expires' => time() - 60, 'scope' => 'read');
        }

        return NULL;
    }

    protected function setAccessToken($oauth_token, $client_id, $expires, $scope = NULL)
    {
        $this->storedAccessTokens[$oauth_token] = array($client_id, $scope, $expires);
    }

    protected function getSupportedGrantTypes()
    {
        return array(OAUTH2_GRANT_TYPE_AUTH_CODE, OAUTH2_GRANT_TYPE_REFRESH_TOKEN);
    }

    protected function setAuthCode($code, $client_id, $redirect_uri, $expires, $scope = NULL)
    {
        $this->storedAuthCodes[$code] = array($client_id, $redirect_uri, $scope, $expires);
    }

    protected function setRefreshToken($refresh_token, $client_id, $expires, $scope = NULL)
    {
        $this->storedRefreshTokens[$refresh_token] = array($client_id, $scope, $expires);
    }
}
