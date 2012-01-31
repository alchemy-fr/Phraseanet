<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ControllerLanguageTest extends PhraseanetWebTestCaseAbstract
{

  protected $client;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRootPost()
  {
    $route = '/language/';

    $this->client->request("GET", $route);
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
    $pageContent = json_decode($this->client->getResponse()->getContent());
    $this->assertTrue(is_object($pageContent));
  }

}
