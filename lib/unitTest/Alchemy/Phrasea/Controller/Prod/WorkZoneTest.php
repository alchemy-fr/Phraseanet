<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

require_once __DIR__ . '/../../../../../Alchemy/Phrasea/Controller/Prod/WorkZone.php';

use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class ControllerWorkZoneTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;
  protected static $need_records = 1;
  protected static $need_story = true;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
    $this->purgeDatabase();
  }

  public function createApplication()
  {
    return require __DIR__ . '/../../../../../Alchemy/Phrasea/Application/Prod.php';
  }

  public function testRootGet()
  {

    $this->insertOneWZ();

    $route = "/WorkZone/";

    $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testAttachStoryToWZ()
  {
    $story = self::$story_1;
    /* @var $story \Record_Adapter */
    $route = sprintf("/WorkZone/attachStories/");
    try
    {
      $this->client->request('POST', $route);
      $this->fail("we can attach none stories to WZ");
    }
    catch (\Exception $e)
    {

    }

    try
    {
      $this->client->request('POST', $route, array('stories' => $story->get_serialize_key()));

      $response = $this->client->getResponse();
    }
    catch (\Exception $e)
    {
      $this->fail($e->getMessage());
    }

    $this->assertEquals(302, $response->getStatusCode());

    $em = self::$core->getEntityManager();
    /* @var $em \Doctrine\ORM\EntityManager */
    $query = $em->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
    );

    $count = $query->getSingleScalarResult();

    $this->assertEquals(1, $count);

    $query = $em->createQuery(
            'SELECT w FROM \Entities\StoryWZ w'
    );

    $storyWZ = $query->getResult();
    $em->remove(array_shift($storyWZ));

    $em->flush();


    //attach JSON
    $this->client->request('POST', $route, array('stories' => $story->get_serialize_key()), array(), array(
        "HTTP_ACCEPT" => "application/json")
    );

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());

    //test already attached
    $this->client->request('POST', $route, array('stories' => $story->get_serialize_key()), array(), array(
        "HTTP_ACCEPT" => "application/json")
    );

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testDetachStoryFromWZ()
  {
    $story = self::$story_1;

    $route = sprintf("/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), $story->get_record_id());
    //story not yet Attched

    try
    {
      $this->client->request('POST', $route);
      $this->fail("Story should not be found");
    }
    catch (\Exception $e)
    {

    }

    //attach
    $attachRoute = sprintf("/WorkZone/attachStories/");
    $this->client->request('POST', $attachRoute, array('stories' => $story->get_serialize_key()));

    $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
    );

    $count = $query->getSingleScalarResult();

    $this->assertEquals(1, $count);


    //detach
    $this->client->request('POST', $route);
    $response = $this->client->getResponse();
    $this->assertEquals(302, $response->getStatusCode());

    $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
    );

    $count = $query->getSingleScalarResult();

    $this->assertEquals(0, $count);

    //attach
    $this->client->request('POST', $attachRoute, array('stories' => $story->get_serialize_key()));

    //detach JSON
    $this->client->request('POST', $route, array(), array(), array(
        "HTTP_ACCEPT" => "application/json")
    );
    $response = $this->client->getResponse();
    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testBrowse()
  {
    $this->client->request("GET", "/WorkZone/Browse/");
    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());
  }

  public function testBrowseSearch()
  {
    $this->client->request("GET", "/WorkZone/Browse/Search/");
    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());
  }

  public function testBrowseBasket()
  {
    $basket = $this->insertOneBasket();
    $this->client->request("GET", "/WorkZone/Browse/Basket/" . $basket->getId() . "/");
    $response = $this->client->getResponse();
    $this->assertTrue($response->isOk());
  }

  public function testDetachStoryFromWZNotFound()
  {
    $story = self::$story_1;

    $route = sprintf("/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), 'unknow');
    //story not yet Attched
  }

}
