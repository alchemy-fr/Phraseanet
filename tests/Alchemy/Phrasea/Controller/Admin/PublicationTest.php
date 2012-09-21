<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class Module_Admin_Route_PublicationTest extends PhraseanetWebTestCaseAuthenticatedAbstract
{
    public static $account = null;
    public static $api = null;
    protected $client;
    protected static $useExceptionHandler = true;

    public function testList()
    {
        $crawler = $this->client->request('GET', '/admin/publications/list/');
        $pageContent = $this->client->getResponse()->getContent();
        $this->assertTrue($this->client->getResponse()->isOk());
        $feeds = Feed_Collection::load_all(self::$application, self::$user);

        foreach ($feeds->get_feeds() as $feed) {
            $this->assertRegExp('/\/admin\/publications\/feed\/' . $feed->get_id() . '/', $pageContent);
            if ($feed->get_collection() != null)
                $this->assertRegExp('/' . $feed->get_collection()->get_name() . '/', $pageContent);
            if ($feed->is_owner(self::$user))
                $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/delete/']")->count());
        }
    }

    public function testCreate()
    {
        $appbox = self::$application['phraseanet.appbox'];

//        foreach ($appbox->get_databoxes() as $databox) {
//            foreach ($databox->get_collections() as $collection) {
//                $base_id = $collection->get_base_id();
//                break;
//            }
//        }
        $feeds = Feed_Collection::load_all(self::$application, self::$user);
        $count = sizeof($feeds->get_feeds());

        $crawler = $this->client->request('POST', '/admin/publications/create/', array("title"    => "hello", "subtitle" => "coucou", "base_id"  => self::$collection->get_base_id()));

        $this->assertTrue($this->client->getResponse()->isRedirect('/admin/publications/list/'));

        $feeds = Feed_Collection::load_all(self::$application, self::$user);
        $count_after = sizeof($feeds->get_feeds());
        $this->assertGreaterThan($count, $count_after);

        $feed = array_pop($feeds->get_feeds());

        $feed->delete();
    }

    public function testGetFeed()
    {
        $appbox = self::$application['phraseanet.appbox'];
        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');
        $crawler = $this->client->request('GET', '/admin/publications/feed/' . $feed->get_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/update/']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='salut']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='coucou']")->count());

        $feed->delete();
    }

    public function testUpdateFeedNotOwner()
    {
        $feed = Feed_Adapter::create(self::$application, self::$user_alt1, "salut", 'coucou');
        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/update/");
        $this->assertTrue($this->client->getResponse()->isRedirect(), 'update fails, i\'m redirected');
        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/?'
            ) === 0);
        $feed->delete();
    }

    public function testUpdatedFeedException()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/update/", array(
            'title'    => 'test'
            , 'subtitle' => 'test'
            , 'public'   => '1'
        ));

        $feed = new Feed_Adapter(self::$application, $feed->get_id());

        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/list/'
            ) === 0);

        $this->assertEquals('test', $feed->get_title());
        $this->assertEquals('test', $feed->get_subtitle());
        $this->assertTrue($feed->is_public());
        $this->assertNull($feed->get_collection());

        $feed->delete();
    }

    public function testUpdatedFeedOwner()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/update/", array(
            'title'    => 'test'
            , 'subtitle' => 'test'
            , 'public'   => '1'
            , 'base_id'  => self::$collection->get_base_id()
        ));

        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/list/'
            ) === 0);


        $feed = new Feed_Adapter(self::$application, $feed->get_id());

        $collection = $feed->get_collection();

        $this->assertEquals('test', $feed->get_title());
        $this->assertEquals('test', $feed->get_subtitle());
        $this->assertFalse($feed->is_public());
        $this->assertEquals(self::$collection->get_base_id(), $collection->get_base_id());

        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/list/'
            ) === 0);

        $feed->delete();
    }

    public function testIconUploadErrorOwner()
    {
        $feed = Feed_Adapter::create(self::$application, self::$user_alt1, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/iconupload/", array(), array(), array('HTTP_ACCEPT' => 'application/json'));

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertFalse($content->success);

        $feed->delete();
    }

    public function testIconUploadErrorFileData()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request(
            "POST"
            , "/admin/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error'   => 1))
        );
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertFalse($content->success);

        $feed->delete();
    }

    public function testIconUploadErrorFileType()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request(
            "POST"
            , "/admin/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error'    => 0, 'tmp_name' => __DIR__ . '/../../../../testfiles/test007.ppt'))
        );
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertFalse($content->success);

        $feed->delete();
    }

    public function testIconUpload()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    __DIR__ . '/../../../../testfiles/logocoll.gif', 'logocoll.gif'
                )
            )
        );

        $this->client->request(
            "POST"
            , "/admin/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , $files
        );

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertTrue($content->success);

        $feed = new Feed_Adapter(self::$application, $feed->get_id());

        $feed->delete();
    }

    public function testAddPublisher()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/addpublisher/", array(
            'usr_id' => self::$user_alt1->get_id()
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());

        $feed = new Feed_Adapter(self::$application, $feed->get_id());
        $publishers = $feed->get_publishers();

        $this->assertTrue(isset($publishers[self::$user_alt1->get_id()]));
        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/'
            ) === 0);

        $feed->delete();
    }

    public function testAddPublisherException()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/addpublisher/");

        $feed = new Feed_Adapter(self::$application, $feed->get_id());
        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/?err'
            ) === 0);

        $feed->delete();
    }

    public function testRemovePublisher()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/removepublisher/", array(
            'usr_id' => self::$user_alt1->get_id()
        ));

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());

        $feed = new Feed_Adapter(self::$application, $feed->get_id());
        $publishers = $feed->get_publishers();

        $this->assertFalse(isset($publishers[self::$user_alt1->get_id()]));
        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/'
            ) === 0);

        $feed->delete();
    }

    public function testRemovePublisherException()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/removepublisher/");

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());

        $feed = new Feed_Adapter(self::$application, $feed->get_id());

        $this->assertTrue(
            strpos(
                $this->client->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/?err'
            ) === 0);

        $feed->delete();
    }

    public function testDeleteFeed()
    {
        $appbox = self::$application['phraseanet.appbox'];

        $feed = Feed_Adapter::create(self::$application, self::$user, "salut", 'coucou');

        $this->client->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/delete/");

        $response = $this->client->getResponse();
        $this->assertTrue($response->isRedirect());

        try {
            $feed = new Feed_Adapter(self::$application, $feed->get_id());
            $feed->delete();
            $this->fail("fail deleting feed");
        } catch (\Exception $e) {

        }
    }
}
