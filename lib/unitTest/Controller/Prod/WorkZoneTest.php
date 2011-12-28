<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class WorkZoneTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = 1;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
    $this->purgeDatabase();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRootGet()
  {

    $this->insertOneWZ();

    $route = "/WorkZone/";

    $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
  }

}