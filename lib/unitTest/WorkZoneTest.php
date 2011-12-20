<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Doctrine\Common\DataFixtures\Loader;
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
  }

  public function createApplication()
  {
    return require __DIR__ . '/../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRootGet()
  {

    $this->loadOneWZ();

    $route = "/WorkZone/";

    $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
  }

  protected function loadOneWZ()
  {
    try
    {
      $currentUser = self::$user;
      $altUser = self::$user_alt1;
      //add one basket
      $basket = new PhraseaFixture\Basket\LoadOneBasket();
      $basket->setUser($currentUser);
      //add one story
      $story = new PhraseaFixture\Story\LoadOneStory();
      $story->setUser($currentUser);
      $story->setRecord(self::$record_1);
      //add a validation session initiated by alt user
      $validationSession = new PhraseaFixture\ValidationSession\LoadOneValidationSession();
      $validationSession->setUser($altUser);

      $loader = new Loader();
      $loader->addFixture($basket);
      $loader->addFixture($story);
      $loader->addFixture($validationSession);

      $this->insertFixtureInDatabase($loader);

      //add current user as participant
      $validationParticipant = new PhraseaFixture\ValidationParticipant\LoadParticipantWithSession();
      $validationParticipant->setSession($validationSession->validationSession);
      $validationParticipant->setUser($currentUser);

      $loader = new Loader();
      $loader->addFixture($validationParticipant);
      $this->insertFixtureInDatabase($loader);
    }
    catch (\Exception $e)
    {
      $this->fail($e->getMessage());
    }

    return;
  }

}