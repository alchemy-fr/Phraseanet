<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

class WorkZoneTest extends \PhraseanetAuthenticatedWebTestCase
{

    protected $client;

    public function testRootGet()
    {
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
        $this->assertBadResponse(self::$DI['client']->getResponse());
    }

    public function testAttachStoryToWZ()
    {
        $story = self::$DI['record_story_2'];
        $route = sprintf("/prod/WorkZone/attachStories/");
        self::$DI['client']->request('POST', $route, ['stories' => [$story->get_serialize_key()]]);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$DI['app']['EM'];
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery('SELECT COUNT(w.id) FROM Phraseanet:StoryWZ w');

        $count = $query->getSingleScalarResult();

        $this->assertEquals(2, $count);
    }

    public function testAttachMultipleStoriesToWZ()
    {
        $story = self::$DI['record_story_1'];
        $route = sprintf("/prod/WorkZone/attachStories/");
        $story2 = self::$DI['record_story_2'];

        $stories = [$story->get_serialize_key(), $story2->get_serialize_key()];

        self::$DI['client']->request('POST', $route, ['stories' => $stories]);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$DI['app']['EM'];
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery('SELECT COUNT(w.id) FROM Phraseanet:StoryWZ w');
        $count = $query->getSingleScalarResult();

        $this->assertEquals(2, $count);
    }

    public function testAttachExistingStory()
    {
        $story = self::$DI['record_story_2'];
        $route = sprintf("/prod/WorkZone/attachStories/");

        $storyWZ = self::$DI['app']['EM']->find('Phraseanet:StoryWZ', 1);

        self::$DI['client']->request('POST', $route, ['stories' => [$story->get_serialize_key()]]);
        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $em = self::$DI['app']['EM'];
        /* @var $em \Doctrine\ORM\EntityManager */
        $query = $em->createQuery(
            'SELECT COUNT(w.id) FROM Phraseanet:StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(2, $count);
    }

    public function testAttachStoryToWZJson()
    {
        $story = self::$DI['record_story_1'];
        $route = sprintf("/prod/WorkZone/attachStories/");
        //attach JSON
        self::$DI['client']->request('POST', $route, ['stories' => [$story->get_serialize_key()]], [], [
            "HTTP_ACCEPT" => "application/json"]
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        //test already attached
        self::$DI['client']->request('POST', $route, ['stories' => [$story->get_serialize_key()]], [], [
            "HTTP_ACCEPT" => "application/json"]
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testDetachStoryFromWZ()
    {
        $story = self::$DI['record_story_2'];

        $route = sprintf("/prod/WorkZone/detachStory/%s/%s/", $story->get_sbas_id(), $story->get_record_id());
        //story not yet Attched

        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());

        //attach
        $attachRoute = sprintf("/prod/WorkZone/attachStories/");
        self::$DI['client']->request('POST', $attachRoute, ['stories' => [$story->get_serialize_key()]]);

        $query = self::$DI['app']['EM']->createQuery(
                'SELECT COUNT(w.id) FROM Phraseanet:StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(2, $count);

        //detach
        self::$DI['client']->request('POST', $route);
        $response = self::$DI['client']->getResponse();
        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$DI['app']['EM']->createQuery(
                'SELECT COUNT(w.id) FROM Phraseanet:StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);

        //attach
        self::$DI['client']->request('POST', $attachRoute, ['stories' => [$story->get_serialize_key()]]);

        //detach JSON
        self::$DI['client']->request('POST', $route, [], [], [
            "HTTP_ACCEPT" => "application/json"]
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
        self::$DI['client']->request("GET", "/prod/WorkZone/Browse/Basket/1/");
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());
    }
}
