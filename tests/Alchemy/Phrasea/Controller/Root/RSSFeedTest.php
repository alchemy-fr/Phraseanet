<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../../../PhraseanetWebTestCaseAbstract.class.inc';
require_once __DIR__ . '/../../../../FeedValidator.inc';

require_once __DIR__ . '/../../../../../lib/Alchemy/Phrasea/Controller/Root/RSSFeeds.php';

use Symfony\Component\HttpFoundation\Response;

class ControllerRssFeedTest extends \PhraseanetWebTestCaseAbstract
{
    /**
     *
     * @var Feed_Adapter
     */
    public static $feed;

    /**
     *
     * @var Feed_Adapter_Entry
     */
    public static $entry;
    public static $publisher;

    /**
     *
     * @var Feed_Collection
     */
    protected static $public_feeds;

    /**
     *
     * @var Feed_Collection
     */
    protected static $private_feeds;

    /**
     *
     * @var Feed_Adapter
     */
    protected static $feed_1_private;
    protected static $feed_1_private_title = 'Feed 1 title';
    protected static $feed_1_private_subtitle = 'Feed 1 subtitle';
    protected static $feed_1_entries = array();
    protected static $feed_2_entries = array();
    protected static $feed_3_entries = array();
    protected static $feed_4_entries = array();

    /**
     *
     * @var Feed_Adapter
     */
    protected static $feed_2_private;
    protected static $feed_2_private_title = 'Feed 2 title';
    protected static $feed_2_private_subtitle = 'Feed 2 subtitle';

    /**
     *
     * @var Feed_Adapter
     */
    protected static $feed_3_public;
    protected static $feed_3_public_title = 'Feed 3 title';
    protected static $feed_3_public_subtitle = 'Feed 3 subtitle';

    /**
     *
     * @var Feed_Adapter
     */
    protected static $feed_4_public;
    protected static $feed_4_public_title = 'Feed 4 title';
    protected static $feed_4_public_subtitle = 'Feed 4 subtitle';
    protected $client;

    public function setUp()
    {
        parent::setUp();
        self::$DI['app.use-exception-handler'] = true;
        self::$feed = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], 'title', 'subtitle');
        self::$publisher = Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], self::$feed, self::$DI['user']);
        self::$entry = Feed_Entry_Adapter::create(self::$DI['app'], self::$feed, self::$publisher, 'title_entry', 'subtitle', 'hello', "test@mail.com");
        Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], self::$entry, self::$DI['record_1']);
        Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], self::$entry, self::$DI['record_2']);
        self::$feed->set_public(true);
    }

    public function tearDown()
    {
        if (self::$publisher instanceof Feed_Publisher_Adapter) {
            self::$publisher->delete();
        }
        if (self::$entry instanceof Feed_Entry_Adapter) {
            self::$entry->delete();
        }
        if (self::$feed instanceof Feed_Adapter) {
            self::$feed->delete();
        }
        parent::tearDown();
    }

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $application = new Alchemy\Phrasea\Application('test');

        @unlink('/tmp/db.sqlite');
        copy(__DIR__ . '/../../../../db-ref.sqlite', '/tmp/db.sqlite');

        $appbox = $application['phraseanet.appbox'];

        $application['session']->clear();
        $application['session']->set('usr_id', self::$DI['user']->get_id());

        self::$feed_1_private = Feed_Adapter::create($application, self::$DI['user'], self::$feed_1_private_title, self::$feed_1_private_subtitle);
        self::$feed_1_private->set_public(false);
        self::$feed_1_private->set_icon(__DIR__ . '/../../../../testfiles/logocoll.gif');

        self::$feed_2_private = Feed_Adapter::create($application, self::$DI['user'], self::$feed_2_private_title, self::$feed_2_private_subtitle);
        self::$feed_2_private->set_public(false);

        self::$feed_3_public = Feed_Adapter::create($application, self::$DI['user'], self::$feed_3_public_title, self::$feed_3_public_subtitle);
        self::$feed_3_public->set_public(true);
        self::$feed_3_public->set_icon(__DIR__ . '/../../../../testfiles/logocoll.gif');

        self::$feed_4_public = Feed_Adapter::create($application, self::$DI['user'], self::$feed_4_public_title, self::$feed_4_public_subtitle);
        self::$feed_4_public->set_public(true);

        $publisher = array_shift(self::$feed_4_public->get_publishers());

        for ($i = 1; $i != 15; $i++) {
            $entry = Feed_Entry_Adapter::create($application, self::$feed_4_public, $publisher, 'titre entry', 'soustitre entry', 'Jean-Marie Biggaro', 'author@example.com');

            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_1']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_6']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_7']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_8']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_9']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_10']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_1']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_13']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_15']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_16']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_19']);

            $entry = Feed_Entry_Adapter::create($application, self::$feed_1_private, $publisher, 'titre entry', 'soustitre entry', 'Jean-Marie Biggaro', 'author@example.com');

            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_1']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_6']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_7']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_8']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_9']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_10']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_1']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_13']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_15']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_16']);
            $item = Feed_Entry_Item::create($appbox, $entry, self::$DI['record_19']);

            self::$feed_4_entries[] = $entry;
        }


        self::$public_feeds = Feed_Collection::load_public_feeds($application);
        self::$private_feeds = Feed_Collection::load_all($application, self::$DI['user']);
        $application['session']->clear();
    }

    public static function tearDownAfterClass()
    {
        self::$feed_1_private->delete();
        self::$feed_2_private->delete();
        self::$feed_3_public->delete();
        self::$feed_4_public->delete();

        parent::tearDownAfterClass();
    }

    public function testPublicFeedAggregated()
    {
        self::$public_feeds->get_aggregate();
        self::$DI['client']->request('GET', '/feeds/aggregated/atom/');
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    protected function evaluateAtom(Response $response)
    {
        $dom_doc = new DOMDocument();
        $dom_doc->preserveWhiteSpace = false;
        $dom_doc->loadXML($response->getContent());

        $xpath = new DOMXPath($dom_doc);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

        $this->assertEquals(1, $xpath->query('/atom:feed/atom:title')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:updated')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:link[@rel="self"]')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:id')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:generator')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:subtitle')->length);
    }

    protected function evaluateGoodXML(Response $response)
    {
        $dom_doc = new DOMDocument();
        $dom_doc->loadXML($response->getContent());
        $this->assertInstanceOf('DOMDocument', $dom_doc);
        $this->assertEquals($dom_doc->saveXML(), $response->getContent());
    }

    protected function evaluateResponse200(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code ');
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
    }

    public function testPublicFeed()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $this->authenticate(self::$DI['app']);

        $link = self::$feed_3_public->get_user_link($appbox->get_registry(), self::$DI['user'], Feed_Adapter::FORMAT_ATOM)->get_href();
        $link = str_replace($appbox->get_registry()->get('GV_ServerName') . 'feeds/', '/', $link);

        $this->logout(self::$DI['app']);

        self::$DI['client']->request('GET', "/feeds" . $link);
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    public function testUserFeedAggregated()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $this->authenticate(self::$DI['app']);

        $link = self::$private_feeds->get_aggregate()->get_user_link($appbox->get_registry(), self::$DI['user'], Feed_Adapter::FORMAT_ATOM)->get_href();
        $link = str_replace($appbox->get_registry()->get('GV_ServerName') . 'feeds/', '/', $link);

        $this->logout(self::$DI['app']);

        self::$DI['client']->request('GET', "/feeds" . $link);
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    public function testUserFeed()
    {
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $this->authenticate(self::$DI['app']);

        $link = self::$feed_1_private->get_user_link($appbox->get_registry(), self::$DI['user'], Feed_Adapter::FORMAT_ATOM)->get_href();
        $link = str_replace($appbox->get_registry()->get('GV_ServerName') . 'feeds/', '/', $link);

        $this->logout(self::$DI['app']);

        self::$DI['client']->request('GET', "/feeds" . $link);
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    public function testGetFeedFormat()
    {
        $feeds = Feed_Collection::load_public_feeds(self::$DI['app']);
        $feed = array_shift($feeds->get_feeds());

        $crawler = self::$DI['client']->request("GET", "/feeds/feed/" . $feed->get_id() . "/rss/");
        $this->assertEquals("application/rss+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();

        $this->verifyXML($xml);
        $this->verifyRSS($feed, $xml);

        $crawler = self::$DI['client']->request("GET", "/feeds/feed/" . $feed->get_id() . "/atom/");
        $this->assertEquals("application/atom+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
        $this->verifyATOM($feed, $xml);
    }

    public function testCooliris()
    {
        $crawler = self::$DI['client']->request("GET", "/feeds/cooliris/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/rss+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
    }

    public function testAggregatedRss()
    {
        $feeds = Feed_Collection::load_public_feeds(self::$DI['app']);
        $all_feeds = $feeds->get_feeds();
        foreach ($all_feeds as $feed) {
            $this->assertTrue($feed->is_public());
        }
        $crawler = self::$DI['client']->request("GET", "/feeds/aggregated/rss/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/rss+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
    }

    public function testAggregatedAtom()
    {
        $feeds = Feed_Collection::load_public_feeds(self::$DI['app']);
        $all_feeds = $feeds->get_feeds();
        foreach ($all_feeds as $feed) {
            $this->assertTrue($feed->is_public());
        }
        $crawler = self::$DI['client']->request("GET", "/feeds/aggregated/atom/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/atom+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
    }

    public function testUnknowFeedId()
    {
        self::$DI['client']->request("GET", "/feeds/feed/0/rss/");
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testUnknowFeedId2()
    {
        self::$DI['client']->request("GET", "/feeds/feed/titi/");
    }

    public function testGetFeedId()
    {
        $feeds = Feed_Collection::load_public_feeds(self::$DI['app']);
        $all_feeds = $feeds->get_feeds();
        $feed = array_shift($all_feeds);

        $crawler = self::$DI['client']->request("GET", "/feeds/feed/" . $feed->get_id() . "/rss/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
        $this->verifyRSS($feed, $xml);

        $crawler = self::$DI['client']->request("GET", "/feeds/feed/" . $feed->get_id() . "/atom/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyATOM($feed, $xml);
    }

    public function testPrivateFeedAccess()
    {
        $private_feed = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], 'title', 'subtitle');
        $private_feed->set_public(false);
        self::$DI['client']->request("GET", "/feeds/feed/" . $private_feed->get_id() . "/rss/");
        $this->assertFalse(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());
        $private_feed->delete();
    }

    public function verifyXML($xml)
    {
        /**
         * XML is not verified due to Validator Service bug
         */
        return;

        try {
            $validator = new W3CFeedRawValidator($xml);
            $response = $validator->validate();

            $this->assertTrue($response->isValid(), $xml . "\n" . $response);
        } catch (W3CFeedValidatorException $e) {
            print "\nCould not use W3C FEED VALIDATOR API : " . $e->getMessage() . "\n";
        }
    }

    function verifyRSS(Feed_Adapter $feed, $xml_string)
    {
        $dom_doc = new DOMDocument();
        $dom_doc->loadXML($xml_string);

        $xpath = new DOMXPath($dom_doc);
        $xpath->registerNamespace("media", "http://search.yahoo.com/mrss/");
        $xpath->registerNamespace("atom", "http://www.w3.org/2005/Atom");
        $xpath->registerNamespace("dc", "http://purl.org/dc/elements/1.1/");
        $this->checkRSSRootNode($xpath, $feed);
        $this->checkRSSEntryNode($xpath, $feed);
    }

    function checkRSSRootNode(DOMXPath $xpath, Feed_Adapter $feed)
    {
        $channel = $xpath->query("/rss/channel");
        foreach ($channel->item(0)->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                switch ($child->nodeName) {
                    case 'title':
                        $this->assertEquals($feed->get_title(), $child->nodeValue);
                        break;
                    case 'dc:title':
                        $this->assertEquals($feed->get_title(), $child->nodeValue);
                        break;
                    case 'description':
                        $this->assertEquals($feed->get_subtitle(), $child->nodeValue);
                        break;
                    case 'link':
                        $this->assertEquals($feed->get_homepage_link(self::$DI['app']['phraseanet.registry'], Feed_Adapter::FORMAT_RSS, 1)->get_href(), $child->nodeValue);
                        break;
                    case 'pubDate':
                        $this->assertTrue(new DateTime() >= new DateTime($child->nodeValue));
                        break;
                    case 'generator':
                        $this->assertEquals("Phraseanet", $child->nodeValue);
                        break;
                    case 'docs':
                        $this->assertEquals("http://blogs.law.harvard.edu/tech/rss", $child->nodeValue);
                        break;
                    case 'atom:link':
                        foreach ($child->attributes as $attribute) {
                            if ($attribute->name == "href") {
                                $this->assertEquals($feed->get_homepage_link(self::$DI['app']['phraseanet.registry'], Feed_Adapter::FORMAT_RSS, 1)->get_href(), $attribute->value);
                                break;
                            }
                        }
                        break;
                }
            }
        }
    }

    function checkRSSEntryNode(DOMXPath $xpath, Feed_Adapter $feed)
    {
        $list_entries = $xpath->query("/rss/channel/item");
        $count = 0;
        $offset_start = 0;
        $n_entries = 20;
        $collection = $feed->get_entries($offset_start, $n_entries);
        $entries = $collection->get_entries();

        foreach ($list_entries as $node) {
            if (sizeof($entries) == 0) {
                $offset_start = ($offset_start++) * $n_entries;
                $collection = $feed->get_entries($offset_start, $n_entries);
                $entries = $collection->get_entries();
                if (sizeof($entries) == 0) //no more
                    break;
            }
            $feed_entry = array_shift($entries);
            switch ($node->nodeName) {
                case 'title':
                    $this->assertEquals($feed_entry->get_title(), $node->nodeValue);
                    break;
                case 'description':
                    $this->assertEquals($feed_entry->get_subtitle(), $node->nodeValue);
                    break;
                case 'author':
                    $author = sprintf(
                        '%s (%s)'
                        , $feed_entry->get_author_email()
                        , $feed_entry->get_author_name()
                    );
                    $this->assertEquals($author, $node->nodeValue);
                    break;
                case 'pubDate':
                    $this->assertEquals($feed_entry->get_created_on()->format(DATE_RFC2822), $node->nodeValue);
                    break;
                case 'guid':
                    $this->assertEquals($feed_entry->get_link()->get_href(), $node->nodeValue);
                    break;
                case 'link':
                    $this->assertEquals($feed_entry->get_link()->get_href(), $node->nodeValue);
                    break;
            }
            $count++;
            $this->checkRSSEntryItemsNode($xpath, $feed_entry, $count);
        }
        $this->assertEquals($feed->get_count_total_entries(), $count);
    }

    function checkRSSEntryItemsNode(DOMXPath $xpath, Feed_Entry_Adapter $entry, $count)
    {
        $content = $entry->get_content();
        $available_medium = array('image', 'audio', 'video');
        array_walk($content, $this->removeBadItems($content, $available_medium));
        $media_group = $xpath->query("/rss/channel/item[" . $count . "]/media:group");
        $this->assertEquals(sizeof($content), $media_group->length);

        foreach ($media_group as $media) {
            $entry_item = array_shift($content);
            $this->verifyMediaItem($entry_item, $media);
        }
    }

    public function verifyMediaItem(Feed_Entry_Item $item, DOMNode $node)
    {
        foreach ($node->childNodes as $node) {
            if ($node->nodeType !== XML_TEXT_NODE) {
                switch ($node->nodeName) {
                    case 'media:content' :
                        $this->checkMediaContentAttributes($item, $node);
                        break;
                    case 'media:thumbnail':
                    default :
                        $this->checkOptionnalMediaGroupNode($node, $item);
                        break;
                }
            }
        }
    }

    public function parseAttributes(DOMNode $node)
    {
        $current_attributes = array();
        foreach ($node->attributes as $attribute) {
            $current_attributes[$attribute->name] = $attribute->value;
        }

        return $current_attributes;
    }

    public function checkMediaContentAttributes(Feed_Entry_Item $entry_item, DOMNode $node)
    {
        $current_attributes = $this->parseAttributes($node);
        $is_thumbnail = false;
        $record = $entry_item->get_record();

        if (substr($current_attributes["url"], 0 - strlen("/preview/")) == "/preview/") {
            $ressource = $record->get_subdef('preview');
        } else {
            $ressource = $record->get_thumbnail();
            $is_thumbnail = true;
        }

        $permalink = $ressource->get_permalink();

        foreach ($current_attributes as $attribute => $value) {
            switch ($attribute) {
                case "url":
                    $this->assertEquals($permalink->get_url(), $value);
                    break;
                case "fileSize":
                    $this->assertEquals($ressource->get_size(), $value);
                    break;
                case "type":
                    $this->assertEquals($ressource->get_mime(), $value);
                    break;
                case "medium":
                    $this->assertEquals(strtolower($record->get_type()), $value);
                    break;
                case "isDefault":
                    !$is_thumbnail ? $this->assertEquals("true", $value) : $this->assertEquals("false", $value);
                    break;
                case "expression":
                    $this->assertEquals("full", $value);
                    break;
                case "bitrate":
                    $this->assertEquals($value);
                    break;
                case "height":
                    $this->assertEquals($ressource->get_height(), $value);
                    break;
                case "width":
                    $this->assertEquals($ressource->get_width(), $value);
                    break;
                case "duration" :
                    $this->assertEquals($record->get_duration(), $value);
                    break;
                case "framerate":
                case "samplingrate":
                case "channels":
                case "lang":
                    break;
                default:
                    $this->fail($attribute . " is not valid");
                    break;
            }
        }
    }

    public function checkOptionnalMediaGroupNode(DOMNode $node, Feed_Entry_Item $entry_item)
    {
        $fields = array(
            'title' => array(
                'dc_field'    => databox_Field_DCESAbstract::Title,
                'media_field' => array(
                    'name'       => 'media:title',
                    'attributes' => array(
                        'type'        => 'plain'
                    )
                ),
                'separator'   => ' '
            )
            , 'description' => array(
                'dc_field'    => databox_Field_DCESAbstract::Description,
                'media_field' => array(
                    'name'       => 'media:description',
                    'attributes' => array()
                ),
                'separator'   => ' '
            )
            , 'contributor' => array(
                'dc_field'    => databox_Field_DCESAbstract::Contributor,
                'media_field' => array(
                    'name'       => 'media:credit',
                    'attributes' => array(
                        'role'      => 'contributor',
                        'scheme'    => 'urn:ebu'
                    )
                ),
                'separator' => ' '
            )
            , 'director'  => array(
                'dc_field'    => databox_Field_DCESAbstract::Creator,
                'media_field' => array(
                    'name'       => 'media:credit',
                    'attributes' => array(
                        'role'      => 'director',
                        'scheme'    => 'urn:ebu'
                    )
                ),
                'separator' => ' '
            )
            , 'publisher' => array(
                'dc_field'    => databox_Field_DCESAbstract::Publisher,
                'media_field' => array(
                    'name'       => 'media:credit',
                    'attributes' => array(
                        'role'      => 'publisher',
                        'scheme'    => 'urn:ebu'
                    )
                ),
                'separator' => ' '
            )
            , 'rights'    => array(
                'dc_field'    => databox_Field_DCESAbstract::Rights,
                'media_field' => array(
                    'name'       => 'media:copyright',
                    'attributes' => array()
                ),
                'separator' => ' '
            )
            , 'keywords'  => array(
                'dc_field'    => databox_Field_DCESAbstract::Subject,
                'media_field' => array(
                    'name'       => 'media:keywords',
                    'attributes' => array()
                ),
                'separator' => ', '
            )
        );


        foreach ($fields as $key_field => $field) {

            $role = true;

            if (isset($field["media_field"]['attributes']['role'])) {
                $role = false;
                foreach ($node->attributes as $attr) {
                    if ($attr->name == 'role') {
                        $role = $attr->value == $field["media_field"]['attributes']['role'];
                        break;
                    }
                }
            }

            if ($field["media_field"]["name"] == $node->nodeName && $role != false) {

                if ($p4field = $entry_item->get_record()->get_caption()->get_dc_field($field["dc_field"])) {
                    $this->assertEquals($p4field->get_serialized_values($field["separator"]), $node->nodeValue, sprintf('Asserting good value for DC %s', $field["dc_field"]));
                    if (sizeof($field["media_field"]["attributes"]) > 0) {
                        foreach ($node->attributes as $attribute) {
                            $this->assertTrue(array_key_exists($attribute->name, $field["media_field"]["attributes"]), "Checkin attribute " . $attribute->name . " for " . $field['media_field']['name']);
                            $this->assertEquals($attribute->value, $field["media_field"]["attributes"][$attribute->name], "Checkin attribute " . $attribute->name . " for " . $field['media_field']['name']);
                        }
                    }
                } else {
                    $this->fail("Missing media:entry");
                }
                break;
            }
        }
    }

    public function removeBadItems(Array &$item_entries, Array $available_medium)
    {
        $remove = function($entry_item, $key) use (&$item_entries, $available_medium) {
                $preview_sd = $entry_item->get_record()->get_subdef('preview');
                $url_preview = $preview_sd->get_permalink();
                $thumbnail_sd = $entry_item->get_record()->get_thumbnail();
                $url_thumb = $thumbnail_sd->get_permalink();

                if (!in_array(strtolower($entry_item->get_record()->get_type()), $available_medium)) {
                    unset($item_entries[$key]); //remove
                }

                if (!$url_thumb || !$url_preview) {
                    unset($item_entries[$key]); //remove
                }
            };

        return $remove;
    }

    public function verifyATOM(Feed_Adapter $feed, $xml_string)
    {
        $this->verifyXML($xml_string);
        $dom_doc = new DOMDocument();
        $dom_doc->loadXML($xml_string);

        $xpath = new DOMXPath($dom_doc);
        $xpath->registerNamespace("media", "http://search.yahoo.com/mrss/");
        $xpath->registerNamespace("Atom", "http://www.w3.org/2005/Atom");

        $this->checkATOMRootNode($dom_doc, $xpath, $feed);
    }

    public function checkATOMRootNode(DOMDocument $dom_doc, DOMXPath $xpath, Feed_Adapter $feed)
    {
        $ids = $xpath->query('/Atom:feed/Atom:id');
        $this->assertEquals($feed->get_homepage_link(self::$DI['app']['phraseanet.registry'], Feed_Adapter::FORMAT_ATOM, 1)->get_href(), $ids->item(0)->nodeValue);

        $titles = $xpath->query('/Atom:feed/Atom:title');
        $this->assertEquals($feed->get_title(), $titles->item(0)->nodeValue);

        $subtitles = $xpath->query('/Atom:feed/Atom:subtitle');
        if ($subtitles->length > 0)
            $this->assertEquals($feed->get_subtitle(), $subtitles->item(0)->nodeValue);

        $updateds = $xpath->query('/Atom:feed/Atom:updated');
        $this->assertTrue(new DateTime() >= new DateTime($updateds->item(0)->nodeValue));

        $entries_item = $xpath->query('/Atom:feed/Atom:entry');

        $count = 0;
        $offset_start = 0;
        $n_entries = 20;
        $collection = $feed->get_entries($offset_start, $n_entries);
        $entries = $collection->get_entries();

        foreach ($entries_item as $entry) {
            if (sizeof($entries) == 0) {
                $offset_start = ($offset_start++) * $n_entries;
                $collection = $feed->get_entries($offset_start, $n_entries);
                $entries = $collection->get_entries();
                if (sizeof($entries) == 0) //no more
                    break;
            }
            $feed_entry = array_shift($entries);
            $this->checkATOMEntryNode($entry, $xpath, $feed, $feed_entry);
            $count++;
        }
        $this->assertEquals($feed->get_count_total_entries(), $count);
    }

    public function checkATOMEntryNode(DOMNode $node, DOMXPath $xpath, Feed_Adapter $feed, Feed_Entry_Adapter $entry)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                switch ($child->nodeName) {
                    case 'id':
                        $this->assertEquals(sprintf('%sentry/%d/', $feed->get_homepage_link(self::$DI['app']['phraseanet.registry'], Feed_Adapter::FORMAT_ATOM, 1)->get_href(), $entry->get_id()), $child->nodeValue);
                        break;
                    case 'link':
                        foreach ($child->attributes as $attribute) {
                            if ($attribute->name == "href") {
                                $this->assertEquals(sprintf('%sentry/%d/', $feed->get_homepage_link(self::$DI['app']['phraseanet.registry'], Feed_Adapter::FORMAT_ATOM, 1)->get_href(), $entry->get_id()), $attribute->value);
                                break;
                            }
                        }
                        break;
                    case 'updated':
                        $this->assertEquals($entry->get_updated_on()->format(DATE_ATOM), $child->nodeValue);
                        break;
                    case 'published':
                        $this->assertEquals($entry->get_created_on()->format(DATE_ATOM), $child->nodeValue);
                        break;
                    case 'title':
                        $this->assertEquals($entry->get_title(), $child->nodeValue);
                        break;
                    case 'content':
                        $this->assertEquals($entry->get_subtitle(), $child->nodeValue);
                        break;
                    case 'author':
                        foreach ($node->childNodes as $child) {
                            if ($child->nodeType !== XML_TEXT_NODE && $child->nodeName == "email")
                                $this->assertEquals($entry->get_author_email(), $child->nodeValue);
                            if ($child->nodeType !== XML_TEXT_NODE && $child->nodeName == "name")
                                $this->assertEquals($entry->get_author_name(), $child->nodeValue);
                        }
                        break;
                }
            }
        }

        $content = $entry->get_content();


        $available_medium = array('image', 'audio', 'video');

        array_walk($content, $this->removeBadItems($content, $available_medium));


        $media_group = $xpath->query("/Atom:feed/Atom:entry[0]/media:group");

        if ($media_group->length > 0) {
            foreach ($media_group as $media) {

                $entry_item = array_shift($content);
                if ($entry_item instanceof Feed_Entry_Item) {
                    $this->verifyMediaItem($entry_item, $media);
                }
            }
        }
    }
}
