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

use Doctrine\Common\DataFixtures\Loader;
use PhraseaFixture\Basket as MyFixture;
use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class storyTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{

  protected $client;

  /**
   *
   * @var \record_adapter
   */
  protected static $need_story = true;
  protected static $need_records = 2;

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

  public function testRootPost()
  {
    $route = "/story/";

    $collections = self::$core->getAuthenticatedUser()
            ->ACL()
            ->get_granted_base(array('canaddrecord'));

    $collection = array_shift($collections);

    $crawler = $this->client->request(
            'POST', $route, array(
        'base_id' => $collection->get_base_id(),
        'name' => 'test story',
        'description' => 'test_description')
    );

    $response = $this->client->getResponse();

    $this->assertEquals(302, $response->getStatusCode());

    $query = self::$core->getEntityManager()->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
    );

    $count = $query->getSingleScalarResult();

    $this->assertEquals(1, $count);
  }

  public function testRootPostJSON()
  {
    $route = "/story/";

    $collections = self::$core->getAuthenticatedUser()
            ->ACL()
            ->get_granted_base(array('canaddrecord'));

    $collection = array_shift($collections);

    $crawler = $this->client->request(
            'POST', $route, array(
        'base_id' => $collection->get_base_id(),
        'name' => 'test story',
        'description' => 'test_description'), array(), array(
        "HTTP_ACCEPT" => "application/json")
    );

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testCreateGet()
  {
    $route = "/story/create/";

    $crawler = $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());

    $filter = "form[action='/prod/story/']";
    $this->assertEquals(1, $crawler->filter($filter)->count());

    $filter = "form[action='/prod/story/'] input[name='name']";
    $this->assertEquals(1, $crawler->filter($filter)->count());

    $filter = "form[action='/prod/story/'] textarea[name='description']";
    $this->assertEquals(1, $crawler->filter($filter)->count());

    $filter = "form[action='/prod/story/'] select[name='base_id']";
    $this->assertEquals(1, $crawler->filter($filter)->count());
  }

  public function testByIds()
  {
    $story = self::$story_1;

    $route = sprintf("/story/%d/%d/", $story->get_sbas_id(), $story->get_record_id());

    $crawler = $this->client->request('GET', $route);

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());
  }

  public function testAddElementsToStory()
  {
    $story = self::$story_1;

    $route = sprintf("/story/%s/%s/addElements/", $story->get_sbas_id(), $story->get_record_id());

    $records = array(
        self::$record_1->get_serialize_key(),
        self::$record_2->get_serialize_key()
    );

    $lst = implode(';', $records);

    $crawler = $this->client->request('POST', $route, array('lst' => $lst));

    $response = $this->client->getResponse();

    $this->assertEquals(302, $response->getStatusCode());

    $this->assertEquals(2, self::$story_1->get_children()->get_count());
  }

  public function testAddElementsToStoryJSON()
  {
    $story = self::$story_1;

    $route = sprintf("/story/%s/%s/addElements/", $story->get_sbas_id(), $story->get_record_id());

    $records = array(
        self::$record_1->get_serialize_key(),
        self::$record_2->get_serialize_key()
    );

    $lst = implode(';', $records);

    $crawler = $this->client->request('POST', $route, array('lst' => $lst)
            , array(), array(
        "HTTP_ACCEPT" => "application/json"));

    $response = $this->client->getResponse();

    $this->assertEquals(200, $response->getStatusCode());

    $this->assertEquals(2, self::$story_1->get_children()->get_count());
  }

  public function testRemoveElementFromStory()
  {
    $story = self::$story_1;

    $records = array(
        self::$record_1,
        self::$record_2
    );

    $totalRecords = count($records);
    $n = 0;
    foreach ($records as $record)
    {
      /* @var $record \record_adapter */
      $route = sprintf(
              "/story/%s/%s/delete/%s/%s/"
              , $story->get_sbas_id()
              , $story->get_record_id()
              , $record->get_sbas_id()
              , $record->get_record_id()
      );

      if (($n % 2) === 0)
      {
        $crawler = $this->client->request('POST', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
      }
      else
      {
        $crawler = $this->client->request(
                'POST', $route, array(), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );
        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
      }
      $n++;

      $this->assertEquals($totalRecords - $n, self::$story_1->get_children()->get_count());
    }
  }

  

  
}