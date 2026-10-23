<?php

use PHPUnit\Framework\TestCase;

class OAuth2ClientTest extends TestCase
{
    protected function tearDown(): void
    {
        $_REQUEST = $_COOKIE = array();
    }

    public function testEndpointUrisAreResolved()
    {
        $client = new OAuth2ClientStub(array(
            'base_uri' => 'https://example.com/',
            'authorize_uri' => 'oauth/authorize',
            'access_token_uri' => 'https://auth.example.com/token',
            'client_id' => 'client',
        ));

        $this->assertSame('https://example.com/oauth/authorize', $client->getVariable('authorize_uri'));
        $this->assertSame('https://auth.example.com/token', $client->getVariable('access_token_uri'));
        $this->assertSame('client', $client->getVariable('client_id'));
    }

    public function testGetUri()
    {
        $client = new OAuth2ClientStub(array('base_uri' => 'https://example.com/'));

        $this->assertSame('https://example.com/api/me?a=1&b=2', $client->callGetUri('/api/me', array('a' => 1, 'b' => 2)));
        $this->assertSame('https://other.example.com/x', $client->callGetUri('https://other.example.com/x'));
    }

    public function testSessionFromRequestIsValidated()
    {
        $client = new OAuth2ClientStub(array('base_uri' => 'https://example.com/', 'client_secret' => 'secret'));
        $session = array('access_token' => 'token', 'expires' => time() + 60);
        $session['sig'] = $client->callGenerateSignature($session, 'secret');
        $_REQUEST['session'] = json_encode($session);

        $this->assertSame($session, $client->getSession());
        $this->assertSame('token', $client->getAccessToken());
    }

    public function testSessionFromCookieIsValidated()
    {
        $client = new OAuth2ClientStub(array('base_uri' => 'https://example.com/', 'client_id' => 'client', 'client_secret' => 'secret', 'cookie_support' => TRUE));
        $session = array('access_token' => 'token', 'expires' => (string) (time() + 60));
        $session['sig'] = $client->callGenerateSignature($session, 'secret');
        $_COOKIE['oauth2_client'] = '"' . http_build_query($session, '', '&') . '"';

        $this->assertSame($session, $client->getSession());
    }

    public function testInvalidSessionIsRejected()
    {
        $client = new OAuth2ClientStub(array('base_uri' => 'https://example.com/', 'client_secret' => 'secret'));
        $_REQUEST['session'] = json_encode(array('access_token' => 'token', 'sig' => 'invalid'));

        $errorLog = ini_set('error_log', '/dev/null');
        try {
            $this->assertNull($client->getSession());
        } finally {
            ini_set('error_log', $errorLog);
        }
    }

    public function testException()
    {
        $exception = new OAuth2Exception(array('code' => '401', 'error' => 'invalid_token'));

        $this->assertSame(401, $exception->getCode());
        $this->assertSame('invalid_token', $exception->getMessage());
        $this->assertSame('invalid_token', $exception->getType());
        $this->assertSame('invalid_token: 401: invalid_token', (string) $exception);

        $exception = new OAuth2Exception(array('message' => 'curl failure'));
        $this->assertSame('Exception', $exception->getType());
        $this->assertSame('curl failure', $exception->getMessage());
    }
}

class OAuth2ClientStub extends OAuth2Client
{
    public function callGetUri($path = '', $params = array())
    {
        return $this->getUri($path, $params);
    }

    public function callGenerateSignature($params, $secret)
    {
        return $this->generateSignature($params, $secret);
    }
}
