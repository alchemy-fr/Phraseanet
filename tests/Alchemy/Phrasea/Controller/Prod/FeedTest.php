<?php

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\CssSelector\CssSelector;

class ControllerFeedApp extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    /**
     *
     * @var Feed_Adapter
     */
    protected $feed;

    /**
     *
     * @var Feed_Entry_Adapter
     */
    protected $entry;

    /**
     *
     * @var Feed_Entry_Item
     */
    protected $item;

    /**
     *
     * @var Feed_Publisher_Adapter
     */
    protected $publisher;
    protected $client;
    protected $feed_title = 'feed title';
    protected $feed_subtitle = 'feed subtitle';
    protected $entry_title = 'entry title';
    protected $entry_subtitle = 'entry subtitle';
    protected $entry_authorname = 'author name';
    protected $entry_authormail = 'author.mail@example.com';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    public function setUp()
    {
        parent::setUp();

        $appbox = appbox::get_instance(\bootstrap::getCore());

        $this->client = $this->createClient();

        $this->feed = Feed_Adapter::create(
                $appbox, self::$user, $this->feed_title, $this->feed_subtitle
        );

        $this->publisher = Feed_Publisher_Adapter::getPublisher(
                $appbox, $this->feed, self::$user
        );

        $this->entry = Feed_Entry_Adapter::create(
                $appbox
                , $this->feed
                , $this->publisher
                , $this->entry_title
                , $this->entry_subtitle
                , $this->entry_authorname
                , $this->entry_authormail
        );

        $this->item = Feed_Entry_Item::create($appbox, $this->entry, static::$records['record_1']);
    }

    public function tearDown()
    {
        if ($this->feed instanceof Feed_Adapter) {
            $this->feed->delete();
        } elseif ($this->entry instanceof Feed_Entry_Adapter) {
            $this->entry->delete();
            if ($this->publisher instanceof Feed_Publisher_Adapter) {
                $this->publisher->delete();
            }
        }

        parent::tearDown();
    }

    public function createApplication()
    {
        $app = require __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Application/Prod.php';

        $app['debug'] = true;
        unset($app['exception_handler']);

        return $app;
    }

    public function testRequestAvailable()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $crawler = $this->client->request('POST', '/feeds/requestavailable/');
        $this->assertTrue($this->client->getResponse()->isOk());
        $feeds = Feed_Collection::load_all($appbox, self::$user);
        foreach ($feeds->get_feeds() as $one_feed) {
            if ($one_feed->is_publisher(self::$user)) {
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
            , 'lst'          => static::$records['record_1']->get_serialize_key()
        );

        $crawler = $this->client->request('POST', '/feeds/entry/create/', $params);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
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
            , 'lst'          => static::$records['record_1']->get_serialize_key()
        );
        $crawler = $this->client->request('POST', '/feeds/entry/create/', $params);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryEdit()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $crawler = $this->client->request('GET', '/feeds/entry/' . $this->entry->get_id() . '/edit/');
        $pageContent = $this->client->getResponse()->getContent();

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
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $feed = Feed_Adapter::create(
                $appbox, self::$user_alt1, $this->feed_title, $this->feed_subtitle
        );

        $publisher = Feed_Publisher_Adapter::getPublisher(
                $appbox, $feed, self::$user_alt1
        );

        $entry = Feed_Entry_Adapter::create(
                $appbox
                , $feed
                , $publisher
                , $this->entry_title
                , $this->entry_subtitle
                , $this->entry_authorname
                , $this->entry_authormail
        );

        try {
            $crawler = $this->client->request('GET', '/feeds/entry/' . $entry->get_id() . '/edit/');
            $this->fail('Should raise an exception');
        } catch (Exception_UnauthorizedAction $e) {

        }

        $feed->delete();
    }

    public function testEntryUpdate()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $params = array(
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => static::$records['record_1']->get_serialize_key(),
        );

        $crawler = $this->client->request('POST', '/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
        $this->assertTrue(is_string($pageContent->datas));
        $this->assertRegExp("/entry_" . $this->entry->get_id() . "/", $pageContent->datas);
    }

    public function testEntryUpdateChangeFeed()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $newfeed = Feed_Adapter::create(
                $appbox, self::$user, $this->feed_title, $this->feed_subtitle
        );

        $params = array(
            "feed_id"      => $newfeed->get_id(),
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => static::$records['record_1']->get_serialize_key(),
        );

        $crawler = $this->client->request('POST', '/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
        $this->assertTrue(is_string($pageContent->datas));
        $this->assertRegExp("/entry_" . $this->entry->get_id() . "/", $pageContent->datas);

        $retrievedentry = Feed_Entry_Adapter::load_from_id($appbox, $this->entry->get_id());
        $this->assertEquals($newfeed->get_id(), $retrievedentry->get_feed()->get_id());

        $newfeed->delete();
    }

    public function testEntryUpdateChangeFeedNoAccess()
    {
        $appbox = \appbox::get_instance(\bootstrap::getCore());
        $newfeed = Feed_Adapter::create(
                $appbox, self::$user, $this->feed_title, $this->feed_subtitle
        );
        $newfeed->set_collection(self::$collection_no_access);

        $appbox = appbox::get_instance(\bootstrap::getCore());

        $params = array(
            "feed_id"      => $newfeed->get_id(),
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_email" => "mouse",
            'lst'          => static::$records['record_1']->get_serialize_key(),
        );

        $crawler = $this->client->request('POST', '/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
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
            'lst'          => static::$records['record_1']->get_serialize_key(),
        );

        $crawler = $this->client->request('POST', '/feeds/entry/' . $this->entry->get_id() . '/update/', $params);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryUpdateNotFound()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $params = array(
            "feed_id"      => 9999999
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_email" => "mouse"
            , 'lst'          => static::$records['record_1']->get_serialize_key()
        );

        $crawler = $this->client->request('POST', '/feeds/entry/99999999/update/', $params);

        $response = $this->client->getResponse();

        $pageContent = json_decode($response->getContent());

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryUpdateFailed()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $params = array(
            "feed_id"      => 9999999
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_email" => "mouse"
            , 'sorted_lst'   => static::$records['record_1']->get_serialize_key() . ";" . static::$records['record_2']->get_serialize_key() . ";12345;" . "unknow_unknow"
        );

        $crawler = $this->client->request('POST', '/feeds/entry/' . $this->entry->get_id() . '/update/', $params);

        $response = $this->client->getResponse();

        $this->assertTrue($response->isOk());
        $pageContent = json_decode($response->getContent());

        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testEntryUpdateUnauthorized()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        /**
         * I CREATE A FEED THAT IS NOT MINE
         * */
        $feed = Feed_Adapter::create($appbox, self::$user_alt1, "salut", 'coucou');
        $publisher = Feed_Publisher_Adapter::getPublisher($appbox, $feed, self::$user_alt1);
        $entry = Feed_Entry_Adapter::create($appbox, $feed, $publisher, "hello", "coucou", "salut", "bonjour@phraseanet.com");
        $item = Feed_Entry_Item::create($appbox, $entry, static::$records['record_1']);

        $params = array(
            "feed_id"      => $feed->get_id()
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_email" => "mouse"
            , 'lst'          => static::$records['record_1']->get_serialize_key()
        );

        $crawler = $this->client->request('POST', '/feeds/entry/' . $entry->get_id() . '/update/', $params);

        $response = $this->client->getResponse();

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
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $crawler = $this->client->request('POST', '/feeds/entry/' . $this->entry->get_id() . '/delete/');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));

        $pageContent = json_decode($this->client->getResponse()->getContent());

        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        try {
            Feed_Entry_Adapter::load_from_id($appbox, $this->entry->get_id());
            $this->fail("Failed to delete entry");
        } catch (Exception $e) {

        }
    }

    public function testDeleteNotFound()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $crawler = $this->client->request('POST', '/feeds/entry/9999999/delete/');

        $response = $this->client->getResponse();

        $pageContent = json_decode($this->client->getResponse()->getContent());

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
    }

    public function testDeleteUnauthorized()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        /**
         * I CREATE A FEED
         * */
        $feed = Feed_Adapter::create($appbox, self::$user_alt1, "salut", 'coucou');

        $publisher = Feed_Publisher_Adapter::getPublisher($appbox, $feed, self::$user_alt1);
        $entry = Feed_Entry_Adapter::create($appbox, $feed, $publisher, "hello", "coucou", "salut", "bonjour@phraseanet.com");
        $item = Feed_Entry_Item::create($appbox, $entry, static::$records['record_1']);

        $crawler = $this->client->request('POST', '/feeds/entry/' . $entry->get_id() . '/delete/');

        $response = $this->client->getResponse();

        $pageContent = json_decode($this->client->getResponse()->getContent());

        $this->assertTrue($response->isOk());
        $this->assertEquals("application/json", $response->headers->get("content-type"));
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        $feed->delete();
    }

    public function testRoot()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $crawler = $this->client->request('GET', '/feeds/');

        $pageContent = $this->client->getResponse()->getContent();

        $this->assertTrue($this->client->getResponse()->isOk());

        $feeds = Feed_Collection::load_all($appbox, self::$user);

        foreach ($feeds->get_feeds() as $one_feed) {

            $path = CssSelector::toXPath("ul.submenu a[href='/prod/feeds/feed/" . $one_feed->get_id() . "/']");
            $msg = sprintf("user %s has access to feed %s", self::$user->get_id(), $one_feed->get_id());

            if ($one_feed->has_access(self::$user)) {
                $this->assertEquals(1, $crawler->filterXPath($path)->count(), $msg);
            } else {
                $this->fail('Feed_collection::load_all should return feed where I got access');
            }
        }
    }

    public function testGetFeed()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        $feeds = Feed_Collection::load_all($appbox, self::$user);

        $crawler = $this->client->request('GET', '/feeds/feed/' . $this->feed->get_id() . "/");
        $pageContent = $this->client->getResponse()->getContent();

        foreach ($feeds->get_feeds() as $one_feed) {
            $path = CssSelector::toXPath("ul.submenu a[href='/prod/feeds/feed/" . $one_feed->get_id() . "/']");
            $msg = sprintf("user %s has access to feed %s", self::$user->get_id(), $one_feed->get_id());

            if ($one_feed->has_access(self::$user)) {
                $this->assertEquals(1, $crawler->filterXPath($path)->count(), $msg);
            } else {
                $this->fail('Feed_collection::load_all should return feed where I got access');
            }
        }
    }

    public function testSuscribeAggregate()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $feeds = Feed_Collection::load_all($appbox, self::$user);
        $crawler = $this->client->request('GET', '/feeds/subscribe/aggregated/');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue(is_string($pageContent->texte));
        $suscribe_link = $feeds->get_aggregate()->get_user_link(registry::get_instance(), self::$user, Feed_Adapter::FORMAT_RSS, null, false)->get_href();
        $this->assertContains($suscribe_link, $pageContent->texte);
    }

    public function testSuscribe()
    {
        $crawler = $this->client->request('GET', '/feeds/subscribe/' . $this->feed->get_id() . '/');
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertEquals("application/json", $this->client->getResponse()->headers->get("content-type"));
        $pageContent = json_decode($this->client->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertTrue(is_string($pageContent->texte));
        $suscribe_link = $this->feed->get_user_link(registry::get_instance(), self::$user, Feed_Adapter::FORMAT_RSS, null, false)->get_href();
        $this->assertContains($suscribe_link, $pageContent->texte);
    }
}
