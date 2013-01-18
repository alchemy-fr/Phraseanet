<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Symfony\Component\HttpKernel\Client;

class ControllerStoryTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public function testRootPost()
    {
        $route = "/prod/story/";

        $collections = self::$DI['app']['phraseanet.user']
            ->ACL()
            ->get_granted_base(array('canaddrecord'));

        $collection = array_shift($collections);

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'base_id' => $collection->get_base_id(),
            'name'    => 'test story'
            )
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $query = self::$DI['app']['EM']->createQuery(
            'SELECT COUNT(w.id) FROM \Entities\StoryWZ w'
        );

        $count = $query->getSingleScalarResult();

        $this->assertEquals(1, $count);
    }

    public function testRootPostJSON()
    {
        $route = "/prod/story/";

        $collections = self::$DI['app']['phraseanet.user']
            ->ACL()
            ->get_granted_base(array('canaddrecord'));

        $collection = array_shift($collections);

        $crawler = self::$DI['client']->request(
            'POST', $route, array(
            'base_id' => $collection->get_base_id(),
            'name'    => 'test story'), array(), array(
            "HTTP_ACCEPT" => "application/json")
        );

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testCreateGet()
    {
        $route = "/prod/story/create/";

        $crawler = self::$DI['client']->request('GET', $route);

        $response = self::$DI['client']->getResponse();

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

        $crawler = self::$DI['client']->request('GET', $route);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testAddElementsToStory()
    {
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        $route = sprintf("/prod/story/%s/%s/addElements/", $story->get_sbas_id(), $story->get_record_id());

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = self::$DI['client']->request('POST', $route, array('lst' => $lst));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(302, $response->getStatusCode());

        $this->assertEquals(2, $story->get_children()->get_count());
        $story->delete();
    }

    public function testAddElementsToStoryJSON()
    {
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        $route = sprintf("/prod/story/%s/%s/addElements/", $story->get_sbas_id(), $story->get_record_id());

        $records = array(
            self::$DI['record_1']->get_serialize_key(),
            self::$DI['record_2']->get_serialize_key()
        );

        $lst = implode(';', $records);

        $crawler = self::$DI['client']->request('POST', $route, array('lst' => $lst)
            , array(), array(
            "HTTP_ACCEPT" => "application/json"));

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals(2, $story->get_children()->get_count());
        $story->delete();
    }

    public function testRemoveElementFromStory()
    {
        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

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

            self::$DI['client'] = new Client(self::$DI['app'], array());

            if (($n % 2) === 0) {
                $crawler = self::$DI['client']->request('POST', $route);

                $response = self::$DI['client']->getResponse();

                $this->assertEquals(302, $response->getStatusCode());
            } else {
                $crawler = self::$DI['client']->request(
                    'POST', $route, array(), array(), array(
                    "HTTP_ACCEPT" => "application/json")
                );
                $response = self::$DI['client']->getResponse();

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
