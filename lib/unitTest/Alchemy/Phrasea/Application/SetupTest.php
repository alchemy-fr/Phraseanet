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
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

}
