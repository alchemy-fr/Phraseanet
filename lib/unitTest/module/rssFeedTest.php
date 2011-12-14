<?php

require_once dirname(__FILE__) . '/../PhraseanetWebTestCaseAbstract.class.inc';
require_once dirname(__FILE__) . '/../FeedValidator.inc';

use Silex\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

class Module_RssFeedTest extends PhraseanetWebTestCaseAbstract
{

  /**
   *
   * @var Feed_Adapter
   */
  public static $feed;
  public static $need_records = 1;
  protected $client;

  public function setUp()
  {
    parent::setUp();
    $this->client = $this->createClient();
  }

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    self::$feed = Feed_Adapter::create(appbox::get_instance(), self::$user, 'title', 'subtitle');
    $publisher = Feed_Publisher_Adapter::getPublisher(appbox::get_instance(), self::$feed, self::$user);
    $entry = Feed_Entry_Adapter::create(appbox::get_instance(), self::$feed, $publisher, 'title_entry', 'subtitle', 'hello', "test@mail.com");
    Feed_Entry_Item::create(appbox::get_instance(), $entry, self::$record_1);
    self::$feed->set_public(true);
  }

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
    self::$feed->delete();
  }

  public function createApplication()
  {
    return require dirname(__FILE__) . '/../../Alchemy/Phrasea/Application/Root.php';
  }

  public function testAggregatedRss()
  {
    $feeds = Feed_Collection::load_public_feeds(appbox::get_instance());
    $all_feeds = $feeds->get_feeds();
    foreach ($all_feeds as $feed)
    {
      $this->assertTrue($feed->is_public());
    }
    $crawler = $this->client->request("GET", "/feeds/aggregated/rss/");
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals("application/rss+xml", $this->client->getResponse()->headers->get("content-type"));
//    $this->assertEquals($feeds->get_aggregate()->get_count_total_entries(), $crawler->filterXPath("//channel/item")->count());
    $xml = $this->client->getResponse()->getContent();
    $this->verifyXML($xml);
  }

  public function testAggregatedAtom()
  {
    $feeds = Feed_Collection::load_public_feeds(appbox::get_instance());
    $all_feeds = $feeds->get_feeds();
    foreach ($all_feeds as $feed)
    {
      $this->assertTrue($feed->is_public());
    }
    $crawler = $this->client->request("GET", "/feeds/aggregated/atom/");
    $this->assertTrue($this->client->getResponse()->isOk());
    $this->assertEquals("application/atom+xml", $this->client->getResponse()->headers->get("content-type"));
    $xml = $this->client->getResponse()->getContent();
    $this->verifyXML($xml);
  }

  public function testUnknowFeedId()
  {
    $crawler = $this->client->request("GET", "/feeds/feed/0/");
    $this->assertFalse($this->client->getResponse()->isOk());
    $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    $crawler = $this->client->request("GET", "/feeds/feed/titi/");
    $this->assertFalse($this->client->getResponse()->isOk());
    $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
  }

  public function testGetFeedFormat()
  {
    $feeds = Feed_Collection::load_public_feeds(appbox::get_instance());
    $feed = array_shift($feeds->get_feeds());
    $crawler = $this->client->request("GET", "/feeds/feed/" . $feed->get_id() . "/rss/");
    $this->assertEquals("application/rss+xml", $this->client->getResponse()->headers->get("content-type"));
    $xml = $this->client->getResponse()->getContent();
    $this->verifyRSS($feed, $xml);
    $crawler = $this->client->request("GET", "/feeds/feed/" . $feed->get_id() . "/atom/");
    $this->assertEquals("application/atom+xml", $this->client->getResponse()->headers->get("content-type"));
    $xml = $this->client->getResponse()->getContent();
    $this->verifyATOM($feed, $xml);
  }

  public function testGetFeedId()
  {
    $feeds = Feed_Collection::load_public_feeds(appbox::get_instance());
    $all_feeds = $feeds->get_feeds();
    foreach ($all_feeds as $feed)
    {
      $crawler = $this->client->request("GET", "/feeds/feed/" . $feed->get_id() . "/rss/");
      $this->assertTrue($this->client->getResponse()->isOk());
      $xml = $this->client->getResponse()->getContent();
      $this->verifyRSS($feed, $xml);
      $crawler = $this->client->request("GET", "/feeds/feed/" . $feed->get_id() . "/atom/");
      $this->assertTrue($this->client->getResponse()->isOk());
      $xml = $this->client->getResponse()->getContent();
      $this->verifyATOM($feed, $xml);
    }
  }

  public function testPrivateFeedAccess()
  {
    $private_feed = Feed_Adapter::create(appbox::get_instance(), self::$user, 'title', 'subtitle');
    $private_feed->set_public(false);
    $this->client->request("GET", "/feeds/feed/" . $private_feed->get_id() . "/rss/");
    $this->assertFalse($this->client->getResponse()->isOk());
    $this->assertEquals(403, $this->client->getResponse()->getStatusCode());
    $private_feed->delete();
  }

  public function verifyXML($xml)
  {
    $this->markTestSkipped("En attente");
    try
    {
      $validator = new W3CFeedRawValidator($xml);
      $response = $validator->validate();

      $this->assertTrue($response->isValid(), $xml . "\n" . $response);
    }
    catch (W3CFeedValidatorException $e)
    {
      $this->fail($e->getMessage());
    }
  }

  function verifyRSS(Feed_Adapter $feed, $xml_string)
  {
    $this->verifyXML($xml_string);

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
    foreach ($channel->item(0)->childNodes as $child)
    {
      if ($child->nodeType !== XML_TEXT_NODE)
      {
        switch ($child->nodeName)
        {
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
            $this->assertEquals($feed->get_homepage_link(registry::get_instance(), Feed_Adapter::FORMAT_RSS, 1)->get_href(), $child->nodeValue);
            break;
          case 'pubDate':
            $this->assertTrue(new DateTime() > new DateTime($child->nodeValue));
            break;
          case 'generator':
            $this->assertEquals("Phraseanet", $child->nodeValue);
            break;
          case 'docs':
            $this->assertEquals("http://blogs.law.harvard.edu/tech/rss", $child->nodeValue);
            break;
          case 'atom:link':
            foreach ($child->attributes as $attribute)
            {
              if ($attribute->name == "href")
              {
                $this->assertEquals($feed->get_homepage_link(registry::get_instance(), Feed_Adapter::FORMAT_RSS, 1)->get_href(), $attribute->value);
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

    foreach ($list_entries as $node)
    {
      if (sizeof($entries) == 0)
      {
        $offset_start = ($offset_start++) * $n_entries;
        $collection = $feed->get_entries($offset_start, $n_entries);
        $entries = $collection->get_entries();
        if (sizeof($entries) == 0) //no more 
          break;
      }
      $feed_entry = array_shift($entries);
      switch ($node->nodeName)
      {
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

    foreach ($media_group as $media)
    {
      $entry_item = array_shift($content);
      $this->verifyMediaItem($entry_item, $media);
    }
  }

  public function verifyMediaItem(Feed_Entry_Item $item, DOMNode $node)
  {
    foreach ($node->childNodes as $node)
    {
      if ($node->nodeType !== XML_TEXT_NODE)
      {
        switch ($node->nodeName)
        {
          case 'media:content' :
            $this->checkMediaContentAttributes($item, $node);
            break;
          case 'media:thumbnail' :
            break;
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
    foreach ($node->attributes as $attribute)
    {
      $current_attributes[$attribute->name] = $attribute->value;
    }
    return $current_attributes;
  }

  public function checkMediaContentAttributes(Feed_Entry_Item $entry_item, DOMNode $node)
  {
    $current_attributes = $this->parseAttributes($node);
    $is_thumbnail = false;
    $record = $entry_item->get_record();

    if (substr($current_attributes["url"], 0 - strlen("/preview/")) == "/preview/")
    {
      $ressource = $record->get_subdef('preview');
    }
    else
    {
      $ressource = $record->get_thumbnail();
      $is_thumbnail = true;
    }

    $permalink = $ressource->get_permalink();

    foreach ($current_attributes as $attribute => $value)
    {
      switch ($attribute)
      {
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
            'dc_field' => databox_Field_DCESAbstract::Title,
            'media_field' => array(
                'name' => 'media:title',
                'attributes' => array(
                    'type' => 'plain'
                )
            ),
            'separator' => ' '
        )
        , 'description' => array(
            'dc_field' => databox_Field_DCESAbstract::Description,
            'media_field' => array(
                'name' => 'media:description',
                'attributes' => array()
            ),
            'separator' => ' '
        )
        , 'contributor' => array(
            'dc_field' => databox_Field_DCESAbstract::Contributor,
            'media_field' => array(
                'name' => 'media:credit',
                'attributes' => array(
                    'role' => 'contributor',
                    'scheme' => 'urn:ebu'
                )
            ),
            'separator' => ' '
        )
        , 'director' => array(
            'dc_field' => databox_Field_DCESAbstract::Creator,
            'media_field' => array(
                'name' => 'media:credit',
                'attributes' => array(
                    'role' => 'creator',
                    'scheme' => 'urn:ebu'
                )
            ),
            'separator' => ' '
        )
        , 'publisher' => array(
            'dc_field' => databox_Field_DCESAbstract::Publisher,
            'media_field' => array(
                'name' => 'media:credit',
                'attributes' => array(
                    'role' => 'publisher',
                    'scheme' => 'urn:ebu'
                )
            ),
            'separator' => ' '
        )
        , 'rights' => array(
            'dc_field' => databox_Field_DCESAbstract::Rights,
            'media_field' => array(
                'name' => 'media:copyright',
                'attributes' => array()
            ),
            'separator' => ' '
        )
        , 'keywords' => array(
            'dc_field' => databox_Field_DCESAbstract::Subject,
            'media_field' => array(
                'name' => 'media:keywords',
                'attributes' => array()
            ),
            'separator' => ', '
        )
    );

    foreach ($fields as $key_field => $field)
    {
      if ($field["media_field"]["name"] == $node->nodeName)
      {
        if ($p4field = $entry_item->get_record()->get_caption()->get_dc_field($field["dc_field"]))
        {
          $this->assertEquals($p4field->get_value(true, $field["separator"]), $node->nodeValue);
          if (sizeof($field["media_field"]["attributes"]) > 0)
          {
            foreach ($node->attributes as $attribute)
            {
              $this->assertTrue(in_array($attribute->name, $field["media_field"]["attributes"]), "MIssing attribute for " . $field['media_field']['name']);
            }
          }
        }
        else
        {
          $this->fail("Missing media:entry");
        }
        break;
      }
    }
  }

  public function removeBadItems(Array &$item_entries, Array $available_medium)
  {
    $remove = function($entry_item, $key) use (&$item_entries, $available_medium)
            {
              $preview_sd = $entry_item->get_record()->get_subdef('preview');
              $url_preview = $preview_sd->get_permalink();
              $thumbnail_sd = $entry_item->get_record()->get_thumbnail();
              $url_thumb = $thumbnail_sd->get_permalink();

              if (!in_array(strtolower($entry_item->get_record()->get_type()), $available_medium))
              {
                unset($item_entries[$key]); //remove
              }

              if (!$url_thumb || !$url_preview)
              {
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
    $this->assertEquals($feed->get_homepage_link(registry::get_instance(), Feed_Adapter::FORMAT_ATOM, 1)->get_href(), $ids->item(0)->nodeValue);

    $titles = $xpath->query('/Atom:feed/Atom:title');
    $this->assertEquals($feed->get_title(), $titles->item(0)->nodeValue);

    $subtitles = $xpath->query('/Atom:feed/Atom:subtitle');
    if ($subtitles->length > 0)
      $this->assertEquals($feed->get_subtitle(), $subtitles->item(0)->nodeValue);

    $updateds = $xpath->query('/Atom:feed/Atom:updated');
    $this->assertTrue(new DateTime() > new DateTime($updateds->item(0)->nodeValue));

    $entries_item = $xpath->query('/Atom:feed/Atom:entry');

    $count = 0;
    $offset_start = 0;
    $n_entries = 20;
    $collection = $feed->get_entries($offset_start, $n_entries);
    $entries = $collection->get_entries();

    foreach ($entries_item as $entry)
    {
      if (sizeof($entries) == 0)
      {
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
    foreach ($node->childNodes as $child)
    {
      if ($child->nodeType !== XML_TEXT_NODE)
      {
        switch ($child->nodeName)
        {
          case 'id':
            $this->assertEquals(sprintf('%sentry/%d/', $feed->get_homepage_link(registry::get_instance(), Feed_Adapter::FORMAT_ATOM, 1)->get_href(), $entry->get_id()), $child->nodeValue);
            break;
          case 'link':
            foreach ($child->attributes as $attribute)
            {
              if ($attribute->name == "href")
              {
                $this->assertEquals(sprintf('%sentry/%d/', $feed->get_homepage_link(registry::get_instance(), Feed_Adapter::FORMAT_ATOM, 1)->get_href(), $entry->get_id()), $attribute->value);
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
            foreach ($node->childNodes as $child)
            {
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
    $media_group = $xpath->query("//media:group");
    $this->assertEquals(sizeof($content), $media_group->length);

    foreach ($media_group as $media)
    {
      $entry_item = array_shift($content);
      $this->verifyMediaItem($entry_item, $media);
    }
  }

}