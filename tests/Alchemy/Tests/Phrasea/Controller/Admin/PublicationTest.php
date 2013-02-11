<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

class Module_Admin_Route_PublicationTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public static $account = null;
    public static $api = null;
    protected $client;

    public function testList()
    {
        $crawler = self::$DI['client']->request('GET', '/admin/publications/list/');
        $pageContent = self::$DI['client']->getResponse()->getContent();
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);

        foreach ($feeds->get_feeds() as $feed) {
            $this->assertRegExp('/\/admin\/publications\/feed\/' . $feed->get_id() . '/', $pageContent);
            if ($feed->get_collection() != null)
                $this->assertRegExp('/' . $feed->get_collection()->get_name() . '/', $pageContent);
            if ($feed->is_owner(self::$DI['user']))
                $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/delete/']")->count());
        }
    }

    public function testCreate()
    {
        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);
        $count = sizeof($feeds->get_feeds());

        $crawler = self::$DI['client']->request('POST', '/admin/publications/create/', array("title"    => "hello", "subtitle" => "coucou", "base_id"  => self::$DI['collection']->get_base_id()));

        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect('/admin/publications/list/'));

        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);
        $count_after = sizeof($feeds->get_feeds());
        $this->assertGreaterThan($count, $count_after);

        $all_feeds = $feeds->get_feeds();
        $feed = array_pop($all_feeds);

        $feed->delete();
    }

    public function testGetFeed()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');
        $crawler = self::$DI['client']->request('GET', '/admin/publications/feed/' . $feed->get_id() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(1, $crawler->filterXPath("//form[@action='/admin/publications/feed/" . $feed->get_id() . "/update/']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='salut']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='coucou']")->count());

        $feed->delete();
    }

    public function testUpdateFeedNotOwner()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user_alt1'], "salut", 'coucou');
        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/update/");
        $this->assertTrue(self::$DI['client']->getResponse()->isRedirect(), 'update fails, i\'m redirected');
        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/?'
            ) === 0);
        $feed->delete();
    }

    public function testUpdatedFeedException()
    {

        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/update/", array(
            'title'    => 'test'
            , 'subtitle' => 'test'
            , 'public'   => '1'
        ));

        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());

        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
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
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/update/", array(
            'title'    => 'test'
            , 'subtitle' => 'test'
            , 'public'   => '1'
            , 'base_id'  => self::$DI['collection']->get_base_id()
        ));

        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/list/'
            ) === 0);


        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());

        $collection = $feed->get_collection();

        $this->assertEquals('test', $feed->get_title());
        $this->assertEquals('test', $feed->get_subtitle());
        $this->assertFalse($feed->is_public());
        $this->assertEquals(self::$DI['collection']->get_base_id(), $collection->get_base_id());

        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/list/'
            ) === 0);

        $feed->delete();
    }

    public function testIconUploadErrorOwner()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user_alt1'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/iconupload/", array(), array(), array('HTTP_ACCEPT' => 'application/json'));

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertFalse($content->success);

        $feed->delete();
    }

    public function testIconUploadErrorFileData()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request(
            "POST"
            , "/admin/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error'   => 1))
        );
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertFalse($content->success);

        $feed->delete();
    }

    public function testIconUploadErrorFileType()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request(
            "POST"
            , "/admin/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , array('Filedata' => array('error'    => 0, 'tmp_name' => __DIR__ . '/../../../../../files/test007.ppt'))
        );
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertFalse($content->success);

        $feed->delete();
    }

    public function testIconUpload()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        $files = array(
            'files' => array(
                new \Symfony\Component\HttpFoundation\File\UploadedFile(
                    __DIR__ . '/../../../../../files/logocoll.gif', 'logocoll.gif'
                )
            )
        );

        self::$DI['client']->request(
            "POST"
            , "/admin/publications/feed/" . $feed->get_id() . "/iconupload/"
            , array()
            , $files
        );

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());

        $content = json_decode($response->getContent());

        $this->assertTrue($content->success);

        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());

        $feed->delete();
    }

    public function testAddPublisher()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/addpublisher/", array(
            'usr_id' => self::$DI['user_alt1']->get_id()
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());
        $publishers = $feed->get_publishers();

        $this->assertTrue(isset($publishers[self::$DI['user_alt1']->get_id()]));
        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/'
            ) === 0);

        $feed->delete();
    }

    public function testAddPublisherException()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/addpublisher/");

        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());
        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/?err'
            ) === 0);

        $feed->delete();
    }

    public function testRemovePublisher()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/removepublisher/", array(
            'usr_id' => self::$DI['user_alt1']->get_id()
        ));

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());
        $publishers = $feed->get_publishers();

        $this->assertFalse(isset($publishers[self::$DI['user_alt1']->get_id()]));
        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/'
            ) === 0);

        $feed->delete();
    }

    public function testRemovePublisherException()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/removepublisher/");

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());

        $this->assertTrue(
            strpos(
                self::$DI['client']->getResponse()->headers->get('Location')
                , '/admin/publications/feed/' . $feed->get_id() . '/?err'
            ) === 0);

        $feed->delete();
    }

    public function testDeleteFeed()
    {
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user'], "salut", 'coucou');

        self::$DI['client']->request("POST", "/admin/publications/feed/" . $feed->get_id() . "/delete/");

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());

        try {
            $feed = new \Feed_Adapter(self::$DI['app'], $feed->get_id());
            $feed->delete();
            $this->fail("fail deleting feed");
        } catch (\Exception $e) {

        }
    }
}
