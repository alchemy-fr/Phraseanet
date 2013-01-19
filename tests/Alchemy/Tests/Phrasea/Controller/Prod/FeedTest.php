<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Symfony\Component\CssSelector\CssSelector;

class ControllerFeedApp extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     *
     * @var \Feed_Adapter
     */
    protected $feed;

    /**
     *
     * @var \Feed_Entry_Adapter
     */
    protected $entry;

    /**
     *
     * @var \Feed_Entry_Item
     */
    protected $item;

    /**
     *
     * @var \Feed_Publisher_Adapter
     */
    protected $publisher;
    protected $client;
    protected $feed_title = 'feed title';
    protected $feed_subtitle = 'feed subtitle';
    protected $entry_title = 'entry title';
    protected $entry_subtitle = 'entry subtitle';
    protected $entry_authorname = 'author name';
    protected $entry_authormail = 'author.mail@example.com';

    public function setUp()
    {
        parent::setUp();


        $this->feed = \Feed_Adapter::create(
                self::$DI['app'], self::$DI['user'], $this->feed_title, $this->feed_subtitle
        );

        $this->publisher = \Feed_Publisher_Adapter::getPublisher(
                self::$DI['app']['phraseanet.appbox'], $this->feed, self::$DI['user']
        );

        $this->entry = \Feed_Entry_Adapter::create(
                self::$DI['app']
                , $this->feed
                , $this->publisher
                , $this->entry_title
                , $this->entry_subtitle
                , $this->entry_authorname
                , $this->entry_authormail
        );

        $this->item = \Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], $this->entry, self::$DI['record_1']);
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');

        self::giveRightsToUser(self::$DI['app'], self::$DI['user']);
        self::$DI['user']->ACL()->revoke_access_from_bases(array(self::$DI['collection_no_access']->get_base_id()));
        self::$DI['user']->ACL()->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
    }

    public function tearDown()
    {
        if ($this->feed instanceof \Feed_Adapter) {
            $this->feed->delete();
        } else if ($this->entry instanceof \Feed_Entry_Adapter) {
            $this->entry->delete();
            if ($this->publisher instanceof \Feed_Publisher_Adapter) {
                $this->publisher->delete();
            }
        }

        parent::tearDown();
    }

    public function testRequestAvailable()
    {
        $crawler = self::$DI['client']->request('POST', '/prod/feeds/requestavailable/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);
        foreach ($feeds->get_feeds() as $one_feed) {
            if ($one_feed->is_publisher(self::$DI['user'])) {
                $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $one_feed->get_id() . "']")->count());
            }
        }
    }

    public function testEntryCreate()
    {
        $params = array(
            "feed_id"      => $this->feed->get_id()
            , "title"        => "salut"
            , "subtitle"     => "coucou"
            , "author_name"  => "robert"
            , "author_email" => "robert@kikoo.mail"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/create/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertFalse($pageContent->message);
    }

    public function testEntryCreateError()
    {
        $params = array(
            "feed_id"      => 'unknow'
            , "title"        => "salut"
            , "subtitle"     => "coucou"
            , "author_name"  => "robert"
            , "author_email" => "robert@kikoo.mail"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );
        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/create/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryEdit()
    {

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/entry/' . $this->entry->get_id() . '/edit/');
        $pageContent = self::$DI['client']->getResponse()->getContent();

        foreach ($this->entry->get_content() as $content) {
            $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $content->get_id() . "' and @name='item_id']")->count());
        }

        $this->assertEquals(1, $crawler->filterXPath("//form[@action='/prod/feeds/entry/" . $this->entry->get_id() . "/update/']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $this->entry_title . "']")->count());
        $this->assertEquals($this->entry_subtitle, $crawler->filterXPath("//textarea[@id='feed_add_subtitle']")->text());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $this->entry_authorname . "']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $this->entry_authormail . "']")->count());
    }

    public function testEntryEditUnauthorized()
    {

        $feed = \Feed_Adapter::create(
                self::$DI['app'], self::$DI['user_alt1'], $this->feed_title, $this->feed_subtitle
        );

        $publisher = \Feed_Publisher_Adapter::getPublisher(
                self::$DI['app']['phraseanet.appbox'], $feed, self::$DI['user_alt1']
        );

        $entry = \Feed_Entry_Adapter::create(
                self::$DI['app']
                , $feed
                , $publisher
                , $this->entry_title
                , $this->entry_subtitle
                , $this->entry_authorname
                , $this->entry_authormail
        );


        try {
            $crawler = self::$DI['client']->request('GET', '/prod/feeds/entry/' . $entry->get_id() . '/edit/');
            $this->fail('Should raise an exception');
        } catch (\Exception_UnauthorizedAction $e) {

        }

        $feed->delete();
    }

    public function testEntryUpdate()
    {

        $params = array(
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
        $this->assertTrue(is_string($pageContent->datas));
        $this->assertRegExp("/entry_" . $this->entry->get_id() . "/", $pageContent->datas);
    }

    public function testEntryUpdateChangeFeed()
    {
        $newfeed = \Feed_Adapter::create(
                self::$DI['app'], self::$DI['user'], $this->feed_title, $this->feed_subtitle
        );

        $params = array(
            "feed_id"      => $newfeed->get_id(),
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
        $this->assertTrue(is_string($pageContent->datas));
        $this->assertRegExp("/entry_" . $this->entry->get_id() . "/", $pageContent->datas);

        $retrievedentry = \Feed_Entry_Adapter::load_from_id(self::$DI['app'], $this->entry->get_id());
        $this->assertEquals($newfeed->get_id(), $retrievedentry->get_feed()->get_id());

        $newfeed->delete();
    }

    public function testEntryUpdateChangeFeedNoAccess()
    {
        $newfeed = \Feed_Adapter::create(
                self::$DI['app'], self::$DI['user'], $this->feed_title, $this->feed_subtitle
        );
        $newfeed->set_collection(self::$DI['collection_no_access']);


        $params = array(
            "feed_id"      => $newfeed->get_id(),
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        $newfeed->delete();
    }

    public function testEntryUpdateChangeFeedInvalidFeed()
    {
        $params = array(
            "feed_id"      => 0,
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryUpdateNotFound()
    {

        $params = array(
            "feed_id"      => 9999999
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_email" => "mouse"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/99999999/update/', $params);

        $response = self::$DI['client']->getResponse();

        $pageContent = json_decode($response->getContent());

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryUpdateFailed()
    {

        $params = array(
            "feed_id"      => 9999999
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_email" => "mouse"
            , 'sorted_lst'   => self::$DI['record_1']->get_serialize_key() . ";" . self::$DI['record_2']->get_serialize_key() . ";12345;" . "unknow_unknow"
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $this->entry->get_id() . '/update/', $params);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $pageContent = json_decode($response->getContent());

        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryUpdateUnauthorized()
    {
        /**
         * I CREATE A FEED THAT IS NOT MINE
         * */
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user_alt1'], "salut", 'coucou');
        $publisher = \Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], $feed, self::$DI['user_alt1']);
        $entry = \Feed_Entry_Adapter::create(self::$DI['app'], $feed, $publisher, "hello", "coucou", "salut", "bonjour@phraseanet.com");
        $item = \Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], $entry, self::$DI['record_1']);

        $params = array(
            "feed_id"      => $feed->get_id()
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_email" => "mouse"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->get_id() . '/update/', $params);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isOk());
        $pageContent = json_decode($response->getContent());

        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        $feed->delete();
    }

    public function testDelete()
    {

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $this->entry->get_id() . '/delete/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        try {
            \Feed_Entry_Adapter::load_from_id(self::$DI['app'], $this->entry->get_id());
            $this->fail("Failed to delete entry");
        } catch (\Exception $e) {

        }
    }

    public function testDeleteNotFound()
    {

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/9999999/delete/');

        $response = self::$DI['client']->getResponse();

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testDeleteUnauthorized()
    {
        /**
         * I CREATE A FEED
         * */
        $feed = \Feed_Adapter::create(self::$DI['app'], self::$DI['user_alt1'], "salut", 'coucou');

        $publisher = \Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], $feed, self::$DI['user_alt1']);
        $entry = \Feed_Entry_Adapter::create(self::$DI['app'], $feed, $publisher, "hello", "coucou", "salut", "bonjour@phraseanet.com");
        $item = \Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], $entry, self::$DI['record_1']);

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->get_id() . '/delete/');

        $response = self::$DI['client']->getResponse();

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        $feed->delete();
    }

    public function testRoot()
    {

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/');

        $pageContent = self::$DI['client']->getResponse()->getContent();

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);

        foreach ($feeds->get_feeds() as $one_feed) {

            $path = CssSelector::toXPath("ul.submenu a[href='/prod/feeds/feed/" . $one_feed->get_id() . "/']");
            $msg = sprintf("user %s has access to feed %s", self::$DI['user']->get_id(), $one_feed->get_id());

            if ($one_feed->has_access(self::$DI['user'])) {
                $this->assertEquals(1, $crawler->filterXPath($path)->count(), $msg);
            } else {
                $this->fail('Feed_collection::load_all should return feed where I got access');
            }
        }
    }

    public function testGetFeed()
    {

        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/feed/' . $this->feed->get_id() . "/");
        $pageContent = self::$DI['client']->getResponse()->getContent();

        foreach ($feeds->get_feeds() as $one_feed) {
            $path = CssSelector::toXPath("ul.submenu a[href='/prod/feeds/feed/" . $one_feed->get_id() . "/']");
            $msg = sprintf("user %s has access to feed %s", self::$DI['user']->get_id(), $one_feed->get_id());

            if ($one_feed->has_access(self::$DI['user'])) {
                $this->assertEquals(1, $crawler->filterXPath($path)->count(), $msg);
            } else {
                $this->fail('Feed_collection::load_all should return feed where I got access');
            }
        }
    }

    public function testSuscribeAggregate()
    {
        $feeds = \Feed_Collection::load_all(self::$DI['app'], self::$DI['user']);
        $crawler = self::$DI['client']->request('GET', '/prod/feeds/subscribe/aggregated/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue(is_string($pageContent->texte));
        $suscribe_link = $feeds->get_aggregate()->get_user_link(self::$DI['app']['phraseanet.registry'], self::$DI['user'], \Feed_Adapter::FORMAT_RSS, null, false)->get_href();
        $this->assertContains($suscribe_link, $pageContent->texte);
    }

    public function testSuscribe()
    {
        $crawler = self::$DI['client']->request('GET', '/prod/feeds/subscribe/' . $this->feed->get_id() . '/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue(is_string($pageContent->texte));
        $suscribe_link = $this->feed->get_user_link(self::$DI['app']['phraseanet.registry'], self::$DI['user'], \Feed_Adapter::FORMAT_RSS, null, false)->get_href();
        $this->assertContains($suscribe_link, $pageContent->texte);
    }
}
