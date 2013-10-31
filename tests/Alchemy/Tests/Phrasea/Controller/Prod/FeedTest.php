<?php

namespace Alchemy\Tests\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\FeedItem;
use Symfony\Component\CssSelector\CssSelector;

class FeedTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
{
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        self::$DI['app'] = new Application('test');

        self::giveRightsToUser(self::$DI['app'], self::$DI['user']);
        self::$DI['app']['acl']->get(self::$DI['user'])->revoke_access_from_bases(array(self::$DI['collection_no_access']->get_base_id()));
        self::$DI['app']['acl']->get(self::$DI['user'])->set_masks_on_base(self::$DI['collection_no_access_by_status']->get_base_id(), '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000', '0000000000000000000000000000000000000000000000000001000000000000');
    }

    public function testRequestAvailable()
    {
        $feed = $this->insertOneFeed(self::$DI['user']);

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/requestavailable/');
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $feeds = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->getAllForUser(self::$DI['user']);
        foreach ($feeds as $one_feed) {
            if ($one_feed->isPublisher(self::$DI['user'])) {
                $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $one_feed->getId() . "' and @name='feed_proposal[]']")->count());
            }
        }
    }

    public function testEntryCreate()
    {
        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['notification.deliverer']->expects($this->atLeastOnce())
            ->method('deliver')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Notification\Mail\MailInfoNewPublication'), $this->equalTo(null));

        $feed = $this->insertOneFeed(self::$DI['user']);
        $params = array(
            "feed_id"        => $feed->getId()
            , "notify"        => 1
            , "title"        => "salut"
            , "subtitle"     => "coucou"
            , "author_name"  => "robert"
            , "author_mail"  => "robert@kikoo.mail"
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
            "feed_id"        => 'unknow'
            , "title"        => "salut"
            , "subtitle"     => "coucou"
            , "author_name"  => "robert"
            , "author_mail"  => "robert@kikoo.mail"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );
        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/create/', $params);
        $this->assertFalse(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testEntryCreateUnauthorized()
    {
        $feed = $this->insertOneFeed(self::$DI['user_alt1']);

        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();

        self::$DI['app']['notification.deliverer']->expects($this->never())
            ->method('deliver');

        $params = array(
            "feed_id"        => $feed->getId()
            , "title"        => "salut"
            , "subtitle"     => "coucou"
            , "author_name"  => "robert"
            , "author_mail"  => "robert@kikoo.mail"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/create/', $params);
        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testEntryEdit()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);
        $crawler = self::$DI['client']->request('GET', '/prod/feeds/entry/' . $entry->getId() . '/edit/');
        $pageContent = self::$DI['client']->getResponse()->getContent();

        foreach ($entry->getItems() as $content) {
            $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $content->getId() . "' and @name='item_id']")->count());
        }

        $this->assertEquals(1, $crawler->filterXPath("//form[@action='/prod/feeds/entry/" . $entry->getId() . "/update/']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $entry->getTitle() . "']")->count());
        $this->assertEquals($entry->getSubtitle(), $crawler->filterXPath("//textarea[@id='feed_add_subtitle']")->text());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $entry->getAuthorName() . "']")->count());
        $this->assertEquals(1, $crawler->filterXPath("//input[@value='" . $entry->getAuthorEmail() . "']")->count());
    }

    public function testEntryEditUnauthorized()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user_alt1']);

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/entry/' . $entry->getId() . '/edit/');
        $pageContent = self::$DI['client']->getResponse();
        $this->assertEquals(403, $pageContent->getStatusCode());
    }

    public function testEntryUpdate()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);

        $params = array(
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_mail"  => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
        $this->assertTrue(is_string($pageContent->datas));
        $this->assertRegExp("/entry_" . $entry->getId() . "/", $pageContent->datas);
    }

    public function testEntryUpdateChangeFeed()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);
        $newfeed = $this->insertOneFeed(self::$DI['user'], "test2");

        $params = array(
            "feed_id"      => $newfeed->getId(),
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_mail"  => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );
        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));
        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());
        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));
        $this->assertTrue(is_string($pageContent->datas));
        $this->assertRegExp("/entry_" . $entry->getId() . "/", $pageContent->datas);

        $retrievedentry = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\FeedEntry')->find($entry->getId());
        $this->assertEquals($newfeed->getId(), $retrievedentry->getFeed()->getId());
    }

    public function testEntryUpdateChangeFeedNoAccess()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);
        $newfeed = $this->insertOneFeed(self::$DI['user_alt1'], "test2");
        $newfeed->setCollection(self::$DI['collection_no_access']);
        self::$DI['app']['EM']->persist($newfeed);
        self::$DI['app']['EM']->flush();

        $params = array(
            "feed_id"      => $newfeed->getId(),
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_mail"  => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);
        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testEntryUpdateChangeFeedInvalidFeed()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);

        $params = array(
            "feed_id"      => 0,
            "title"        => "dog",
            "subtitle"     => "cat",
            "author_name"  => "bird",
            "author_mail"  => "mouse",
            'lst'          => self::$DI['record_1']->get_serialize_key(),
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);
        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testEntryUpdateNotFound()
    {

        $params = array(
            "feed_id"        => 9999999
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_mail"  => "mouse"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/99999999/update/', $params);

        $response = self::$DI['client']->getResponse();

        $pageContent = json_decode($response->getContent());

        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testEntryUpdateFailed()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);

        $params = array(
            "feed_id"        => 9999999
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_mail"  => "mouse"
            , 'sorted_lst'   => self::$DI['record_1']->get_serialize_key() . ";" . self::$DI['record_2']->get_serialize_key() . ";12345;" . "unknow_unknow"
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testEntryUpdateUnauthorized()
    {
        /**
         * I CREATE A FEED THAT IS NOT MINE
         * */

        $entry = $this->insertOneFeedEntry(self::$DI['user_alt1']);

        $params = array(
            "feed_id"      => $entry->getFeed()->getId()
            , "title"        => "dog"
            , "subtitle"     => "cat"
            , "author_name"  => "bird"
            , "author_mail"  => "mouse"
            , 'lst'          => self::$DI['record_1']->get_serialize_key()
        );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);

        $response = self::$DI['client']->getResponse();

        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());;
    }

    public function testEntryUpdateChangeOrder()
    {
        $item1 = $this->insertOneFeedItem(self::$DI['user']);
        $entry = $item1->getEntry();
        $item2 = new FeedItem();
        $item2->setEntry($entry)
            ->setRecordId(self::$DI['record_2']->get_record_id())
            ->setSbasId(self::$DI['record_2']->get_sbas_id());
        $entry->addItem($item2);

        self::$DI['app']['EM']->persist($entry);
        self::$DI['app']['EM']->persist($item2);
        self::$DI['app']['EM']->flush();

        $ord1 = $item1->getOrd();
        $ord2 = $item2->getOrd();

        $params = array(
            "title"         => $entry->getTitle(),
            "author_name"   => $entry->getAuthorName(),
            "author_mail"   => $entry->getAuthorEmail(),
            'sorted_lst'    => $item1->getId() . '_' . $item2->getOrd() . ';'
                             . $item2->getId() . '_' . $item1->getOrd()
            );

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/update/', $params);
        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $newItem1 = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\FeedItem')->find($item1->getId());
        $newItem2 = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\FeedItem')->find($item2->getId());

        $this->assertEquals($ord1, (int) $newItem2->getOrd());
        $this->assertEquals($ord2, (int) $newItem1->getOrd());
    }

    public function testDelete()
    {
        $entry = $this->insertOneFeedEntry(self::$DI['user']);

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/delete/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertTrue(is_object($pageContent));
        $this->assertFalse($pageContent->error);
        $this->assertTrue(is_string($pageContent->message));

        try {
            self::$DI["app"]['EM']->getRepository('Alchemy\Phrasea\Model\Entities\FeedEntry')->find($entry->getId());
            $this->fail("Failed to delete entry");
        } catch (\Exception $e) {

        }
    }

    public function testDeleteNotFound()
    {

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/9999999/delete/');

        $response = self::$DI['client']->getResponse();

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertEquals(404, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testDeleteUnauthorized()
    {
        /**
         * I CREATE A FEED
         * */
        $entry = $this->insertOneFeedEntry(self::$DI['user_alt1']);

        $crawler = self::$DI['client']->request('POST', '/prod/feeds/entry/' . $entry->getId() . '/delete/');

        $response = self::$DI['client']->getResponse();

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent());

        $this->assertEquals(403, self::$DI['client']->getResponse()->getStatusCode());
    }

    public function testRoot()
    {

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/');

        $pageContent = self::$DI['client']->getResponse()->getContent();

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());

        $feeds = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->getAllForUser(self::$DI['user']);

        foreach ($feeds as $one_feed) {

            $path = CssSelector::toXPath("ul.submenu a[href='/prod/feeds/feed/" . $one_feed->getId() . "/']");
            $msg = sprintf("user %s has access to feed %s", self::$DI['user']->getId(), $one_feed->getId());

            if ($one_feed->has_access(self::$DI['user'])) {
                $this->assertEquals(1, $crawler->filterXPath($path)->count(), $msg);
            } else {
                $this->fail('FeedRepository::getAllForUser should return feeds I am allowed to access');
            }
        }
    }

    public function testGetFeed()
    {

        $feed = $this->insertOneFeed(self::$DI['user']);

        $feeds = self::$DI['app']['EM']->getRepository('Alchemy\Phrasea\Model\Entities\Feed')->getAllForUser(self::$DI['user']);

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/feed/' . $feed->getId() . "/");
        $pageContent = self::$DI['client']->getResponse()->getContent();

        foreach ($feeds as $one_feed) {
            $path = CssSelector::toXPath("ul.submenu a[href='/prod/feeds/feed/" . $one_feed->getId() . "/']");
            $msg = sprintf("user %s has access to feed %s", self::$DI['user']->get_id(), $one_feed->getId());

            if ($one_feed->hasAccess(self::$DI['user'], self::$DI['app'])) {
                $this->assertEquals(1, $crawler->filterXPath($path)->count(), $msg);
            } else {
                $this->fail('FeedRepository::getAllForUser should return feeds I am allowed to access');
            }
        }
    }

    public function testSuscribeAggregate()
    {
        $feed = $this->insertOneFeed(self::$DI['user']);

        self::$DI['app']['feed.aggregate-link-generator'] = $this->getMockBuilder('Alchemy\Phrasea\Feed\Link\AggregateLinkGenerator')
            ->disableOriginalConstructor()
            ->getMock();
        $link = $this->getMockBuilder('Alchemy\Phrasea\Feed\Link\FeedLink')
            ->disableOriginalConstructor()
            ->getMock();
        $link->expects($this->once())
            ->method('getURI')
            ->will($this->returnValue('http://aggregated-link/'));
        self::$DI['app']['feed.aggregate-link-generator']->expects($this->once())
            ->method('generate')
            ->with($this->isInstanceOf('Alchemy\Phrasea\Feed\Aggregate'), $this->isInstanceOf('\User_Adapter'), 'rss', null, false)
            ->will($this->returnValue($link));

        self::$DI['client']->request('GET', '/prod/feeds/subscribe/aggregated/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent(), true);

        $this->assertArrayHasKey('texte', $pageContent);
        $this->assertArrayHasKey('titre', $pageContent);

        $this->assertInternalType('string', $pageContent['texte']);
        $this->assertInternalType('string', $pageContent['titre']);

        $this->assertContains('http://aggregated-link/', $pageContent['texte']);
    }

    public function testSuscribe()
    {
        $feed = $this->insertOneFeed(self::$DI['user']);

        self::$DI['app']['feed.user-link-generator'] = $this->getMockBuilder('Alchemy\Phrasea\Feed\Link\FeedLinkGenerator')
            ->disableOriginalConstructor()
            ->getMock();
        $link = $this->getMockBuilder('Alchemy\Phrasea\Feed\Link\FeedLink')
            ->disableOriginalConstructor()
            ->getMock();
        $link->expects($this->once())
            ->method('getURI')
            ->will($this->returnValue('http://user-link/'));
        self::$DI['app']['feed.user-link-generator']->expects($this->once())
            ->method('generate')
            ->with($this->isInstanceOf('\Alchemy\Phrasea\Model\Entities\Feed'), $this->isInstanceOf('\User_Adapter'), 'rss', null, false)
            ->will($this->returnValue($link));

        $crawler = self::$DI['client']->request('GET', '/prod/feeds/subscribe/' . $feed->getId() . '/');

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
        $this->assertEquals("application/json", self::$DI['client']->getResponse()->headers->get("content-type"));

        $pageContent = json_decode(self::$DI['client']->getResponse()->getContent(), true);

        $this->assertArrayHasKey('texte', $pageContent);
        $this->assertArrayHasKey('titre', $pageContent);

        $this->assertInternalType('string', $pageContent['texte']);
        $this->assertInternalType('string', $pageContent['titre']);

        $this->assertContains('http://user-link/', $pageContent['texte']);
    }
}
