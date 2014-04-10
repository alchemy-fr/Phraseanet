<?php

namespace Alchemy\Tests\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Client;
use Alchemy\Phrasea\Core\Event\Subscriber\ApiCorsSubscriber;

class ApiCorsSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $origin = 'http://dev.phrasea.net';

    public function testHostRestriction()
    {
        $response = $this->request(array('enabled' => true, 'hosts' => array('http://api.domain.com')));
        $this->assertArrayNotHasKey('access-control-allow-origin', $response->headers->all());

        $response = $this->request(array('enabled' => true, 'hosts' => array('localhost')));
        $this->assertArrayHasKey('access-control-allow-origin', $response->headers->all());
    }

    public function testExposeHeaders()
    {
        $response = $this->request(
            array('enabled' => true, 'allow_origin' => array('*'), 'expose_headers' => array('HTTP_X_CUSTOM')),
            'GET'
        );
        $this->assertArrayHasKey('access-control-expose-headers', $response->headers->all());
        $this->assertEquals('http_x_custom', $response->headers->get('access-control-expose-headers'));
    }

    public function testAllowMethods()
    {
        $response = $this->request(
            array('enabled' => true, 'allow_origin' => array('*'), 'allow_methods' => array('GET', 'POST', 'PUT')),
            'OPTIONS'
        );
        $this->assertArrayHasKey('access-control-allow-methods', $response->headers->all());
        $this->assertEquals(implode(', ', array('GET', 'POST', 'PUT')), $response->headers->get('access-control-allow-methods'));
    }

    public function testAllowHeaders()
    {
        $response = $this->request(
            array('enabled' => true, 'allow_origin' => array('*'), 'allow_headers' => array('HTTP_X_CUSTOM')),
            'OPTIONS'
        );
        $this->assertArrayHasKey('access-control-allow-headers', $response->headers->all());
        $this->assertEquals('http_x_custom', $response->headers->get('access-control-allow-headers'));
    }

    public function testCORSIsEnable()
    {
        $response = $this->request(array('enabled' => true));
        $this->assertArrayHasKey('access-control-allow-origin', $response->headers->all());
    }

    public function testCORSIsDisable()
    {
        $response = $this->request(array('enabled' => false));
        $this->assertArrayNotHasKey('access-control-allow-origin', $response->headers->all());
    }

    public function testAllowOrigin()
    {
        $response = $this->request(array('enabled' => true, 'allow_origin' => array('*')));
        $this->assertArrayHasKey('access-control-allow-origin', $response->headers->all());
        $this->assertEquals($this->origin, $response->headers->get('access-control-allow-origin'));
    }

    public function testCredentialIsEnabled()
    {
        $response = $this->request(array('enabled' => true, 'allow_credentials' => true, 'allow_origin' => array('*')));
        $this->assertArrayHasKey('access-control-allow-credentials', $response->headers->all());
    }

    /**
     * @param array  $conf
     * @param string $method
     * @param array  $extraHeaders
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function request(array $conf, $method = 'GET', array $extraHeaders = array())
    {
        $app = new Application('test');
        $app['phraseanet.configuration']['api_cors'] = $conf;
        $app['dispatcher']->addSubscriber(new ApiCorsSubscriber($app));
        $app->get('/api/v1/test-route', function () {
            return '';
        });
        $client = new Client($app);
        $client->request($method, '/api/v1/test-route',
            array(),
            array(),
            array_merge(
                $extraHeaders,
                array(
                    'HTTP_Origin'    => $this->origin,
                )
            )
        );

        return $client->getResponse();
    }
}
