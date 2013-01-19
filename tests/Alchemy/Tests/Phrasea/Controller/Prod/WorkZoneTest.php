<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class ControllerWorkZoneTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{

    protected $client;

    public function testRootGet()
    {
        $this->insertOneWZ();

        $route = "/prod/WorkZone/";

        self::$DI['client']->request('GET', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAttachStoryToWZBadRequest()
    {
        /* @var $story \Record_Adapter */
        $route = sprintf("/prod/WorkZone/attachStories/");

        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($response->isOk());
    }

    public function testAttachStoryToWZ()
    {
        $story = self::$DI['record_story_1'];
        $route = sprintf("/prod/WorkZone/attachStories/");
        self::$DI['client']->request('POST', $route, array('stories' => array($story->get_serialize_key())));
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$DI['app']['EM'];
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery(
                'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);
    }

    public function testAttachMultipleStoriesToWZ()
    {
        $story = self::$DI['record_story_1'];
        $route = sprintf("/prod/WorkZone/attachStories/");
        $story2 = self::$DI['record_story_2'];

        $stories = array($story->get_serialize_key(), $story2->get_serialize_key());

        self::$DI['client']->request('POST', $route, array('stories' => $stories));
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$DI['app']['EM'];
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
    }

    public function testAttachExistingStory()
    {
        $story = self::$DI['record_story_1'];
        $route = sprintf("/prod/WorkZone/attachStories/");

        $StoryWZ = new \Entities\StoryWZ();
        $StoryWZ->setUser(self::$DI['app']['phraseanet.user']);
        $StoryWZ->setRecord($story);

        self::$DI['app']['EM']->persist($StoryWZ);
        self::$DI['app']['EM']->flush();

        self::$DI['client']->request('POST', $route, array('stories' => array($story->get_serialize_key())));
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$DI['app']['EM'];
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery(
                'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);
    }

    public function testAttachStoryToWZJson()
    {
        $story = self::$DI['record_story_1'];
        $route = sprintf("/prod/WorkZone/attachStories/");
        //attach JSON
        self::$DI['client']->request('POST', $route, array('stories' => array($story->get_serialize_key())), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        //test already attached
        self::$DI['client']->request('POST', $route, array('stories' => array($story->get_serialize_key())), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDetachStoryFromWZ()
    {
        $story = self::$DI['record_story_1'];

        $route = sprintf("/prod/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), $story->get_record_id());
        //story not yet Attched

        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($response->isOk());

        //attach
        $attachRoute = sprintf("/prod/WorkZone/attachStories/");
        self::$DI['client']->request('POST', $attachRoute, array('stories' => array($story->get_serialize_key())));

        $query = self::$DI['app']['EM']->createQuery(
                'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);


        //detach
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$DI['app']['EM']->createQuery(
                'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(0, $count);

        //attach
        self::$DI['client']->request('POST', $attachRoute, array('stories' => array($story->get_serialize_key())));

        //detach JSON
        self::$DI['client']->request('POST', $route, array(), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testBrowse()
    {
        self::$DI['client']->request("GET", "/prod/WorkZone/Browse/");
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testBrowseSearch()
    {
        self::$DI['client']->request("GET", "/prod/WorkZone/Browse/Search/");
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testBrowseBasket()
    {
        $basket = $this->insertOneBasket();
        self::$DI['client']->request("GET", "/prod/WorkZone/Browse/Basket/" . $basket->getId() . "/");
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }

    public function testDetachStoryFromWZNotFound()
    {
        $story = self::$DI['record_story_1'];

        $route = sprintf("/prod/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), 'unknow');
        //story not yet Attched
    }

}
