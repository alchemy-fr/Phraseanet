<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../Alchemy/Phrasea/Application/Setup.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationSetupTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = false;

  public function createApplication()
  {
    return require __DIR__ . '/../../../../Alchemy/Phrasea/Application/Setup.php';
  }

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function testRouteSlash()
  {
    $crawler = $this->client->request('GET', '/');
    $response = $this->client->getResponse();
    $this->assertEquals(302, $response->getStatusCode());
    $this->assertEquals('/login/', $response->headers->get('location'));
  }

}
