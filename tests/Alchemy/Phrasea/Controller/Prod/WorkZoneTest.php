<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Helper;
use Alchemy\Phrasea\RouteProcessor as routeProcessor;

class ControllerWorkZoneTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    protected $client;

    public function setUp()
    {
        parent::setUp();
        $this->client = $this->createClient();
        $this->purgeDatabase();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';
        
        $app['debug'] = true;
        unset($app['exception_handler']);
        
        return $app;
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
        $story = static::$records['record_story_1'];
        /* @var $story \Record_Adapter */
        $route = sprintf("/WorkZone/attachStories/");

        $this->client->request('POST', $route);
        $response = $this->client->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($response->isOk());

        $this->client->request('POST', $route, array('stories' => array($story->get_serialize_key())));
        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);

        $story2 = static::$records['record_story_2'];

        $stories = array($story->get_serialize_key(), $story2->get_serialize_key());

        $this->client->request('POST', $route, array('stories' => $stories));
        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());



        $em = self::$core->getEntityManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(2, $count);

        $query = $em->createQuery(
            'SELECT w FROM \Entities\StoryWZ w'
        );

        $storyWZ = $query->getResult();
        $em->remove(array_shift($storyWZ));

        $em->flush();


    //attach JSON
    $this->client->request('POST', $route, array('stories' => array($story->get_serialize_key())), array(), array(
        "HTTP_ACCEPT" => "application/json")
    );

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

    //test already attached
    $this->client->request('POST', $route, array('stories' => array($story->get_serialize_key())), array(), array(
        "HTTP_ACCEPT" => "application/json")
    );

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDetachStoryFromWZ()
    {
        $story = static::$records['record_story_1'];

        $route = sprintf("/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), $story->get_record_id());
        //story not yet Attched

        $this->client->request('POST', $route);
        $response = $this->client->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($response->isOk());

        //attach
        $attachRoute = sprintf("/WorkZone/attachStories/");
        $this->client->request('POST', $attachRoute, array('stories' => array($story->get_serialize_key())));

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
        $this->client->request('POST', $attachRoute, array('stories' => array($story->get_serialize_key())));

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
        $story = static::$records['record_story_1'];

        $route = sprintf("/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), 'unknow');
        //story not yet Attched
    }
}
