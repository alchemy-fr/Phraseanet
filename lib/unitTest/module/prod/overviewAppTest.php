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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpFoundation\Response;

class Feed_overviewTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected static $need_records = 1;
  protected static $need_subdefs = true;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
  }

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
  }

  public function createApplication()
  {
    return require dirname(__FILE__) . '/../../../../lib/Alchemy/Phrasea/Application/Overview.php';
  }

//$deliver_content = function(session $session, record_adapter $record, $subdef, $watermark, $stamp, $app)
//
//$app->get('/datafiles/{sbas_id}/{record_id}/{subdef}/'

  function testDatafilesRouteAuthenticated()
  {
//    $this->client->followRedirects();
    $registry = registry::get_instance();
    $crawler = $this->client->request('GET', '/datafiles/' . self::$record_1->get_sbas_id() . '/' . self::$record_1->get_record_id() . '/preview/');
    $response = $this->client->getResponse();

    if (self::$record_1->get_preview()->get_baseurl() !== '')
    {
      $this->assertEquals(302, $response->getStatusCode());
      $url = p4string::delEndSlash($registry->get('GV_ServerName')) . $response->headers->get('Location');
      $headers = http_query::getHttpHeaders($url);
      $this->assertEquals(self::$record_1->get_preview()->get_mime(), $headers['content_type']);
      $this->assertEquals(self::$record_1->get_preview()->get_size(), $headers['download_content_length']);
    }
    else
    {
      $this->assertEquals(200, $response->getStatusCode());
      $content_disposition = explode(';', $response->headers->get('content-disposition'));
      $this->assertEquals($content_disposition[0], 'attachment');
      $this->assertEquals(self::$record_1->get_preview()->get_mime(), $response->headers->get('content-type'));
      $this->assertEquals(self::$record_1->get_preview()->get_size(), $response->headers->get('content-length'));
    }

    $crawler = $this->client->request('GET', '/datafiles/' . self::$record_1->get_sbas_id() . '/' . self::$record_1->get_record_id() . '/asubdefthatdoesnotexists/');
    $response = $this->client->getResponse();

    $this->assertEquals(404, $response->getStatusCode());
  }

  function testDatafilesRouteNotAuthenticated()
  {
    $appbox = appbox::get_instance();
    $appbox->get_session()->logout();
    $crawler = $this->client->request('GET', '/datafiles/' . self::$record_1->get_sbas_id() . '/' . self::$record_1->get_record_id() . '/preview/');
    $response = $this->client->getResponse();
    $this->assertEquals(403, $response->getStatusCode());

    $crawler = $this->client->request('GET', '/datafiles/' . self::$record_1->get_sbas_id() . '/' . self::$record_1->get_record_id() . '/notfoundreview/');
    $response = $this->client->getResponse();
    $this->assertEquals(403, $response->getStatusCode());
  }

  function testPermalinkAuthenticated()
  {
    $appbox = appbox::get_instance();
    $this->assertTrue($appbox->get_session()->is_authenticated());
    $this->get_a_permalink();
  }

  function testPermalinkNotAuthenticated()
  {
    $appbox = appbox::get_instance();
    $appbox->get_session()->logout();
    $this->assertFalse($appbox->get_session()->is_authenticated());
    $this->get_a_permalink();
  }

  protected function get_a_permalink()
  {
    $token = self::$record_1->get_preview()->get_permalink()->get_token();
    $url = '/permalink/v1/whateverIwannt/' . self::$record_1->get_sbas_id() . '/' . self::$record_1->get_record_id() . '/' . $token . '/preview/';

    $crawler = $this->client->request('GET', $url);
    $response = $this->client->getResponse();
    
    if (self::$record_1->get_preview()->get_baseurl() !== '')
    {
      $this->assertEquals(302, $response->getStatusCode());
    }
    else
    {
      $this->assertEquals(200, $response->getStatusCode());
    }

    $url = $url . 'view/';
    $crawler = $this->client->request('GET', $url);
    $response = $this->client->getResponse();
    $this->assertEquals(200, $response->getStatusCode());
  }

}
