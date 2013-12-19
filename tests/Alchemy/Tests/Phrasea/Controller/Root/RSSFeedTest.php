<?php

namespace Alchemy\Tests\Phrasea\Controller\Root;

require_once __DIR__ . '/../../../../../classes/FeedValidator.inc';

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Feed;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Symfony\Component\HttpFoundation\Response;

class RssFeedTest extends \PhraseanetWebTestCase
{
    public function testPublicFeedAggregated()
    {
        self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Feed', 2);

        self::$DI['client']->request('GET', '/feeds/aggregated/atom/');
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    private function evaluateAtom(Response $response)
    {
        $dom_doc = new \DOMDocument();
        $dom_doc->preserveWhiteSpace = false;
        $dom_doc->loadXML($response->getContent());

        $xpath = new \DOMXPath($dom_doc);
        $xpath->registerNamespace('atom', 'http://www.w3.org/2005/Atom');

        $this->assertEquals(1, $xpath->query('/atom:feed/atom:title')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:updated')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:link[@rel="self"]')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:id')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:generator')->length);
        $this->assertEquals(1, $xpath->query('/atom:feed/atom:subtitle')->length);
    }

    private function evaluateGoodXML(Response $response)
    {
        $dom_doc = new \DOMDocument();
        $dom_doc->loadXML($response->getContent());
        $this->assertInstanceOf('DOMDocument', $dom_doc);
        $this->assertEquals($dom_doc->saveXML(), $response->getContent());
    }

    private function evaluateResponse200(Response $response)
    {
        $this->assertEquals(200, $response->getStatusCode(), 'Test status code ');
        $this->assertEquals('UTF-8', $response->getCharset(), 'Test charset response');
    }

    public function testPublicFeed()
    {
        $this->authenticate(self::$DI['app']);
        $feed = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Feed', 2);

        self::$DI['client']->request('GET', "/feeds/feed/" . $feed->getId() . "/atom/");
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    public function testUserFeedAggregated()
    {
        $token = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\AggregateToken', 1);
        $tokenValue = $token->getValue();

        $this->logout(self::$DI['app']);

        self::$DI['client']->request('GET', "/feeds/userfeed/aggregated/$tokenValue/atom/");
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    public function testUserFeed()
    {
        $token = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\FeedToken', 1);
        $tokenValue = $token->getValue();
        $this->logout(self::$DI['app']);

        self::$DI['client']->request('GET', "/feeds/userfeed/$tokenValue/".$token->getFeed()->getId()."/atom/");
        $response = self::$DI['client']->getResponse();

        $this->evaluateResponse200($response);
        $this->evaluateGoodXML($response);

        $this->evaluateAtom($response);
    }

    public function testGetFeedFormat()
    {
        $feed = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Feed', 2);
        self::$DI['client']->request("GET", "/feeds/feed/" . $feed->getId() . "/rss/");

        $this->assertEquals("application/rss+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();

        $this->verifyXML($xml);
        $this->verifyRSS($feed, $xml);

        self::$DI['client']->request("GET", "/feeds/feed/" . $feed->getId() . "/atom/");
        $this->assertEquals("application/atom+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
        $this->verifyATOM($feed, $xml);
    }

    public function testCooliris()
    {
        self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Feed', 2);

        self::$DI['client']->request("GET", "/feeds/cooliris/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/rss+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
    }

    public function testAggregatedRss()
    {
        $all_feeds = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->findBy(['public' => true], ['updatedOn' => 'DESC']);

        foreach ($all_feeds as $feed) {
            $this->assertTrue($feed->isPublic());
        }
        self::$DI['client']->request("GET", "/feeds/aggregated/rss/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/rss+xml", self::$DI['client']->getResponse()->headers->get("content-type"));
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
    }

    public function testAggregatedAtom()
    {
        $all_feeds = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->findBy(['public' => true], ['updatedOn' => 'DESC']);

        foreach ($all_feeds as $feed) {
            $this->assertTrue($feed->isPublic());
        }
        self::$DI['client']->request("GET", "/feeds/aggregated/atom/");
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

    public function testUnknowFeedId2()
    {
        self::$DI['client']->request("GET", "/feeds/feed/titi/");

        $this->assertNotFoundResponse(self::$DI['client']->getResponse());
    }

    public function testGetFeedId()
    {
        $feed = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Feed', 2);

        self::$DI['client']->request("GET", "/feeds/feed/" . $feed->getId() . "/rss/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyXML($xml);
        $this->verifyRSS($feed, $xml);

        self::$DI['client']->request("GET", "/feeds/feed/" . $feed->getId() . "/atom/");
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $xml = self::$DI['client']->getResponse()->getContent();
        $this->verifyATOM($feed, $xml);
    }

    public function testPrivateFeedAccess()
    {
        $feed = self::$DI['app']['EM']->find('Alchemy\Phrasea\Model\Entities\Feed', 1);
        self::$DI['client']->request("GET", "/feeds/feed/" . $feed->getId() . "/rss/");
        $this->assertFalse(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());
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

    public function verifyRSS(Feed $feed, $xml_string)
    {
        $dom_doc = new \DOMDocument();
        $dom_doc->loadXML($xml_string);

        $xpath = new \DOMXPath($dom_doc);
        $xpath->registerNamespace("media", "http://search.yahoo.com/mrss/");
        $xpath->registerNamespace("atom", "http://www.w3.org/2005/Atom");
        $xpath->registerNamespace("dc", "http://purl.org/dc/elements/1.1/");
        $this->checkRSSRootNode($xpath, $feed);
        $this->checkRSSEntryNode($xpath, $feed);
    }

    public function checkRSSRootNode(\DOMXPath $xpath, Feed $feed)
    {
        $channel = $xpath->query("/rss/channel");
        foreach ($channel->item(0)->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                switch ($child->nodeName) {
                    case 'title':
                        $this->assertEquals($feed->getTitle(), $child->nodeValue);
                        break;
                    case 'dc:title':
                        $this->assertEquals($feed->getTitle(), $child->nodeValue);
                        break;
                    case 'description':
                        $this->assertEquals($feed->getSubtitle(), $child->nodeValue);
                        break;
                    case 'link':
                        $this->assertEquals(self::$DI['app']['feed.user-link-generator']->generatePublic($feed, 'rss', 1)->getURI(), $child->nodeValue);
                        break;
                    case 'pubDate':
                        $this->assertTrue(new \DateTime() >= new \DateTime($child->nodeValue));
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
                                $this->assertEquals(self::$DI['app']['feed.user-link-generator']->generatePublic($feed, 'rss', 1)->getURI(), $attribute->value);
                                break;
                            }
                        }
                        break;
                }
            }
        }
    }

    public function checkRSSEntryNode(\DOMXPath $xpath, Feed $feed)
    {
        $list_entries = $xpath->query("/rss/channel/item");
        $count = 0;
        $offset_start = 0;
        $n_entries = 20;
        $entries = $feed->getEntries($offset_start, $n_entries);

        foreach ($list_entries as $node) {
            if (sizeof($entries) == 0) {
                $offset_start = ($offset_start++) * $n_entries;
                $entries = $feed->getEntries($offset_start, $n_entries);
                if (sizeof($entries) == 0) //no more
                    break;
            }
            $feed_entry = array_shift($entries);
            switch ($node->nodeName) {
                case 'title':
                    $this->assertEquals($feed_entry->getTitle(), $node->nodeValue);
                    break;
                case 'description':
                    $this->assertEquals($feed_entry->getSubtitle(), $node->nodeValue);
                    break;
                case 'author':
                    $author = sprintf(
                        '%s (%s)'
                        , $feed_entry->getAuthorEmail()
                        , $feed_entry->getAuthorName()
                    );
                    $this->assertEquals($author, $node->nodeValue);
                    break;
                case 'pubDate':
                    $this->assertEquals($feed_entry->getCreatedOn()->format(DATE_RFC2822), $node->nodeValue);
                    break;
                case 'guid':
                    $this->assertEquals($feed_entry->getLink()->getURI(), $node->nodeValue);
                    break;
                case 'link':
                    $this->assertEquals($feed_entry->getLink()->getURI(), $node->nodeValue);
                    break;
            }
            $count++;
            $this->checkRSSEntryItemsNode($xpath, $feed_entry, $count);
        }
        $this->assertEquals($feed->getCountTotalEntries(), $count);
    }

    public function checkRSSEntryItemsNode(\DOMXPath $xpath, FeedEntry $entry, $count)
    {
        $content = $entry->getItems()->toArray();
        $available_medium = ['image', 'audio', 'video'];
        array_walk($content, $this->removeBadItems($content, $available_medium));
        $media_group = $xpath->query("/rss/channel/item[" . $count . "]/media:group");
        $this->assertEquals(sizeof($content), $media_group->length, sizeof($content)." != ".$media_group->length);

        foreach ($media_group as $media) {
            $entry_item = array_shift($content);
            $this->verifyMediaItem($entry_item, $media);
        }
    }

    public function verifyMediaItem(FeedItem $item, \DOMNode $node)
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

    public function parseAttributes(\DOMNode $node)
    {
        $current_attributes = [];
        foreach ($node->attributes as $attribute) {
            $current_attributes[$attribute->name] = $attribute->value;
        }

        return $current_attributes;
    }

    public function checkMediaContentAttributes(FeedItem $entry_item, \DOMNode $node)
    {
        $current_attributes = $this->parseAttributes($node);
        $is_thumbnail = false;
        $record = $entry_item->getRecord(self::$DI['app']);

        if (false !== strpos($current_attributes["url"], 'preview')) {
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

    public function checkOptionnalMediaGroupNode(\DOMNode $node, FeedItem $entry_item)
    {
        $fields = [
            'title' => [
                'dc_field'    => \databox_Field_DCESAbstract::Title,
                'media_field' => [
                    'name'       => 'media:title',
                    'attributes' => [
                        'type'        => 'plain'
                    ]
                ],
                'separator'   => ' '
            ]
            , 'description' => [
                'dc_field'    => \databox_Field_DCESAbstract::Description,
                'media_field' => [
                    'name'       => 'media:description',
                    'attributes' => []
                ],
                'separator'   => ' '
            ]
            , 'contributor' => [
                'dc_field'    => \databox_Field_DCESAbstract::Contributor,
                'media_field' => [
                    'name'       => 'media:credit',
                    'attributes' => [
                        'role'      => 'contributor',
                        'scheme'    => 'urn:ebu'
                    ]
                ],
                'separator' => ' '
            ]
            , 'director'  => [
                'dc_field'    => \databox_Field_DCESAbstract::Creator,
                'media_field' => [
                    'name'       => 'media:credit',
                    'attributes' => [
                        'role'      => 'director',
                        'scheme'    => 'urn:ebu'
                    ]
                ],
                'separator' => ' '
            ]
            , 'publisher' => [
                'dc_field'    => \databox_Field_DCESAbstract::Publisher,
                'media_field' => [
                    'name'       => 'media:credit',
                    'attributes' => [
                        'role'      => 'publisher',
                        'scheme'    => 'urn:ebu'
                    ]
                ],
                'separator' => ' '
            ]
            , 'rights'    => [
                'dc_field'    => \databox_Field_DCESAbstract::Rights,
                'media_field' => [
                    'name'       => 'media:copyright',
                    'attributes' => []
                ],
                'separator' => ' '
            ]
            , 'keywords'  => [
                'dc_field'    => \databox_Field_DCESAbstract::Subject,
                'media_field' => [
                    'name'       => 'media:keywords',
                    'attributes' => []
                ],
                'separator' => ', '
            ]
        ];

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

                if ($p4field = $entry_item->getRecord(self::$DI['app'])->get_caption()->get_dc_field($field["dc_field"])) {
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
        $remove = function ($entry_item, $key) use (&$item_entries, $available_medium) {
                $preview_sd = $entry_item->getRecord(self::$DI['app'])->get_subdef('preview');
                $url_preview = $preview_sd->get_permalink();
                $thumbnail_sd = $entry_item->getRecord(self::$DI['app'])->get_thumbnail();
                $url_thumb = $thumbnail_sd->get_permalink();

                if (!in_array(strtolower($entry_item->getRecord(self::$DI['app'])->get_type()), $available_medium)) {
                    unset($item_entries[$key]); //remove
                }

                if (!$url_thumb || !$url_preview) {
                    unset($item_entries[$key]); //remove
                }
            };

        return $remove;
    }

    public function verifyATOM(Feed $feed, $xml_string)
    {
        $this->verifyXML($xml_string);
        $dom_doc = new \DOMDocument();
        $dom_doc->loadXML($xml_string);

        $xpath = new \DOMXPath($dom_doc);
        $xpath->registerNamespace("media", "http://search.yahoo.com/mrss/");
        $xpath->registerNamespace("Atom", "http://www.w3.org/2005/Atom");

        $this->checkATOMRootNode($dom_doc, $xpath, $feed);
    }

    public function checkATOMRootNode(\DOMDocument $dom_doc, \DOMXPath $xpath, Feed $feed)
    {
        $ids = $xpath->query('/Atom:feed/Atom:id');
        $this->assertEquals(self::$DI['app']['feed.user-link-generator']->generatePublic($feed, 'atom', 1)->getURI(), $ids->item(0)->nodeValue);

        $titles = $xpath->query('/Atom:feed/Atom:title');
        $this->assertEquals($feed->getTitle(), $titles->item(0)->nodeValue);

        $subtitles = $xpath->query('/Atom:feed/Atom:subtitle');
        if ($subtitles->length > 0)
            $this->assertEquals($feed->getSubtitle(), $subtitles->item(0)->nodeValue);

        $updateds = $xpath->query('/Atom:feed/Atom:updated');
        $this->assertTrue(new \DateTime() >= new \DateTime($updateds->item(0)->nodeValue));

        $entries_item = $xpath->query('/Atom:feed/Atom:entry');

        $count = 0;
        $offset_start = 0;
        $n_entries = 20;
        $entries = $feed->getEntries($offset_start, $n_entries);

        foreach ($entries_item as $entry) {
            if (sizeof($entries) == 0) {
                $offset_start = ($offset_start++) * $n_entries;
                $entries = $feed->getEntries($offset_start, $n_entries);
                if (sizeof($entries) == 0) //no more
                    break;
            }
            $feed_entry = array_shift($entries);
            $this->checkATOMEntryNode($entry, $xpath, $feed, $feed_entry);
            $count++;
        }
        $this->assertEquals($feed->getCountTotalEntries(), $count);
    }

    public function checkATOMEntryNode(\DOMNode $node, \DOMXPath $xpath, Feed $feed, FeedEntry $entry)
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_TEXT_NODE) {
                switch ($child->nodeName) {
                    case 'id':

                        $this->assertEquals(sprintf('%sentry/%d/', self::$DI['app']['feed.user-link-generator']->generatePublic($feed, 'atom', 1)->getURI(), $entry->getId()), $child->nodeValue);
                        break;
                    case 'link':
                        foreach ($child->attributes as $attribute) {
                            if ($attribute->name == "href") {
                                $this->assertEquals(sprintf('%sentry/%d/', self::$DI['app']['feed.user-link-generator']->generatePublic($feed, 'atom', 1)->getURI(), $entry->getId()), $attribute->value);
                                break;
                            }
                        }
                        break;
                    case 'updated':
                        $this->assertEquals($entry->getUpdatedOn()->format(DATE_ATOM), $child->nodeValue);
                        break;
                    case 'published':
                        $this->assertEquals($entry->getCreatedOn()->format(DATE_ATOM), $child->nodeValue);
                        break;
                    case 'title':
                        $this->assertEquals($entry->getTitle(), $child->nodeValue);
                        break;
                    case 'content':
                        $this->assertEquals($entry->getSubtitle(), $child->nodeValue);
                        break;
                    case 'author':
                        foreach ($node->childNodes as $child) {
                            if ($child->nodeType !== XML_TEXT_NODE && $child->nodeName == "email")
                                $this->assertEquals($entry->getAuthorEmail(), $child->nodeValue);
                            if ($child->nodeType !== XML_TEXT_NODE && $child->nodeName == "name")
                                $this->assertEquals($entry->getAuthorName(), $child->nodeValue);
                        }
                        break;
                }
            }
        }

        $content = $entry->getItems()->toArray();

        $available_medium = ['image', 'audio', 'video'];

        array_walk($content, $this->removeBadItems($content, $available_medium));

        $media_group = $xpath->query("/Atom:feed/Atom:entry[0]/media:group");

        if ($media_group->length > 0) {
            foreach ($media_group as $media) {

                $entry_item = array_shift($content);
                if ($entry_item instanceof FeedEntry) {
                    $this->verifyMediaItem($entry_item, $media);
                }
            }
        }
    }
}
