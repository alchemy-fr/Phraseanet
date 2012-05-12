<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

class ApplicationOverviewTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
    }

    public function createApplication()
    {
        return require __DIR__ . '/../../../../lib/Alchemy/Phrasea/Application/Overview.php';
    }

    function testDatafilesRouteAuthenticated()
    {
        $registry = registry::get_instance();
        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/preview/');
        $response = $this->client->getResponse();

        if (static::$records['record_1']->get_preview()->get_baseurl() !== '') {
            $this->assertEquals(302, $response->getStatusCode());
            $url = p4string::delEndSlash($registry->get('GV_ServerName')) . $response->headers->get('Location');
            $headers = http_query::getHttpHeaders($url);
            $this->assertEquals(static::$records['record_1']->get_preview()->get_mime(), $headers['content_type']);
            $this->assertEquals(static::$records['record_1']->get_preview()->get_size(), $headers['download_content_length']);
        } else {
            $this->assertEquals(200, $response->getStatusCode());
            $content_disposition = explode(';', $response->headers->get('content-disposition'));
            $this->assertEquals($content_disposition[0], 'attachment');
            $this->assertEquals(static::$records['record_1']->get_preview()->get_mime(), $response->headers->get('content-type'));
            $this->assertEquals(static::$records['record_1']->get_preview()->get_size(), $response->headers->get('content-length'));
        }

        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/asubdefthatdoesnotexists/');
        $response = $this->client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
    }

    function testDatafilesRouteNotAuthenticated()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $appbox->get_session()->logout();
        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/preview/');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());

        $crawler = $this->client->request('GET', '/datafiles/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/notfoundreview/');
        $response = $this->client->getResponse();
        $this->assertEquals(403, $response->getStatusCode());
    }

    function testPermalinkAuthenticated()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $this->assertTrue($appbox->get_session()->is_authenticated());
        $this->get_a_permalink();
    }

    function testPermalinkNotAuthenticated()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $appbox->get_session()->logout();
        $this->assertFalse($appbox->get_session()->is_authenticated());
        $this->get_a_permalink();
    }

    protected function get_a_permalink()
    {
        $token = static::$records['record_1']->get_preview()->get_permalink()->get_token();
        $url = '/permalink/v1/whateverIwannt/' . static::$records['record_1']->get_sbas_id() . '/' . static::$records['record_1']->get_record_id() . '/' . $token . '/preview/';

        $crawler = $this->client->request('GET', $url);
        $response = $this->client->getResponse();

        if (static::$records['record_1']->get_preview()->get_baseurl() !== '') {
            $this->assertEquals(302, $response->getStatusCode());
        } else {
            $this->assertEquals(200, $response->getStatusCode());
        }

        $url = $url . 'view/';
        $crawler = $this->client->request('GET', $url);
        $response = $this->client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }
}
