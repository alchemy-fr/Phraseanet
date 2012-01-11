<?php

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../Alchemy/Phrasea/Application/Root.php';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ApplicationRootTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = false;

  public function createApplication()
  {
    return require __DIR__ . '/../../../../Alchemy/Phrasea/Application/Root.php';
  }
  
  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function tearDown()
  {
    $this->feed->delete();
    parent::tearDown();
  }

  public function testRouteSlash()
  {
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

}
