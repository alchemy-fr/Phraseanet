<?php

use Symfony\Component\HttpKernel\Client;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';
require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/Prod/Story.php';

class ControllerStoryTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function testRootPost()
    {
        $route = "/prod/story/";

        $collections = self::$application['phraseanet.user']
            ->ACL()
            ->get_granted_base(array('canaddrecord'));

        $collection = array_shift($collections);

        $crawler = $this->client->request(
            'POST', $route, array(
            'base_id' => $collection->get_base_id(),
            'name'    => 'test story'
            )
        );

        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$application['EM']->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);
    }

    public function testRootPostJSON()
    {
        $route = "/prod/story/";

        $collections = self::$application['phraseanet.user']
            ->ACL()
            ->get_granted_base(array('canaddrecord'));

        $collection = array_shift($collections);

        $crawler = $this->client->request(
            'POST', $route, array(
            'base_id' => $collection->get_base_id(),
            'name'    => 'test story'), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateGet()
    {
        $route = "/prod/story/create/";

        $crawler = $this->client->request('GET', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $filter = "form[action='/prod/story/']";
        $this->assertEquals(1, $crawler->filter($filter)->count());

        $filter = "form[action='/prod/story/'] input[name='name']";
        $this->assertEquals(1, $crawler->filter($filter)->count());

        $filter = "form[action='/prod/story/'] select[name='base_id']";
        $this->assertEquals(1, $crawler->filter($filter)->count());
    }

    public function testByIds()
    {
        $story = self::$DI['record_story_1'];

        $route = sprintf("/prod/story/%d/%d/", $story->get_sbas_id(), $story->get_record_id());

        $crawler = $this->client->request('GET', $route);

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAddElementsToStory()
    {
        $story = \record_adapter::createStory(self::$application, self::$collection);

        $route = sprintf("/prod/story/%s/%s/addElements/", $story->get_sbas_id(), $story->get_record_id());

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = $this->client->request('POST', $route, array('lst' => $lst));

        $response = $this->client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals(2, $story->get_children()->get_count());
        $story->delete();
    }

    public function testAddElementsToStoryJSON()
    {
        $story = \record_adapter::createStory(self::$application, self::$collection);

        $route = sprintf("/prod/story/%s/%s/addElements/", $story->get_sbas_id(), $story->get_record_id());

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = $this->client->request('POST', $route, array('lst' => $lst)
            , array(), array(
            "HTTP_ACCEPT" => "application/json"));

        $response = $this->client->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(2, $story->get_children()->get_count());
        $story->delete();
    }

    public function testRemoveElementFromStory()
    {
        $story = \record_adapter::createStory(self::$application, self::$collection);

        $records = array(
            self::$DI['record_1'],
            self::$DI['record_2']
        );

        foreach($records as $record) {
            $story->appendChild($record);
        }

        $totalRecords = $story->get_children()->get_count();
        $n = 0;
        foreach ($records as $record) {
            /* @var $record \record_adapter */
            $route = sprintf(
                "/prod/story/%s/%s/delete/%s/%s/"
                , $story->get_sbas_id()
                , $story->get_record_id()
                , $record->get_sbas_id()
                , $record->get_record_id()
            );

            $this->client = new Client(self::$application, array());

            if (($n % 2) === 0) {
                $crawler = $this->client->request('POST', $route);

                $response = $this->client->getResponse();

                $this->assertEquals(302, $response->getStatusCode());
            } else {
                $crawler = $this->client->request(
                    'POST', $route, array(), array(), array(
                    "HTTP_ACCEPT" => "application/json")
                );
                $response = $this->client->getResponse();

                $this->assertEquals(200, $response->getStatusCode());

                $data = json_decode($response->getContent(), true);
                $this->assertTrue($data['success']);
            }
            $n ++;
            $this->assertEquals($totalRecords - $n, $story->get_children()->get_count());
        }
        $story->delete();
    }
}
