<?php

namespace Alchemy\Tests\Phrasea\Model\Repositories;

use Alchemy\Phrasea\Model\Entities\FeedItem;

class FeedItemRepositoryTest extends \PhraseanetTestCase
{
    public function testIs_record_in_public_feedInPublicFeed()
    {
        $record = self::$DI['record_7'];
        $this->assertTrue(self::$DI['app']['orm.em']->getRepository('Phraseanet:FeedItem')->isRecordInPublicFeed(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id()));
    }

    public function testIs_record_in_public_feedInPrivateFeed()
    {
        $record = self::$DI['record_2'];
        $this->assertFalse(self::$DI['app']['orm.em']->getRepository('Phraseanet:FeedItem')->isRecordInPublicFeed(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id()));
    }

    public function testLoadLatestItems()
    {
        $this->assertCount(3, self::$DI['app']['orm.em']->getRepository('Phraseanet:FeedItem')->loadLatest(self::$DI['app'], 20));
    }

    public function testLoadLatestItemsLessItems()
    {
        $this->assertCount(1, self::$DI['app']['orm.em']->getRepository('Phraseanet:FeedItem')->loadLatest(self::$DI['app'], 1));
    }

    public function testLoadLatestWithDeletedDatabox()
    {
        $feed = self::$DI['app']['orm.em']->find('Phraseanet:Feed', 2);
        $entry = $feed->getEntries()->first();
        $item = new FeedItem();
        $item->setEntry($entry)
            ->setOrd(4)
            ->setRecordId(self::$DI['record_1']->get_record_id())
            ->setSbasId(0);
        $entry->addItem($item);

        self::$DI['app']['orm.em']->persist($item);

        $item = new FeedItem();
        $item->setEntry($entry)
            ->setOrd(4)
            ->setRecordId(0)
            ->setSbasId(self::$DI['record_1']->get_sbas_id());
        $entry->addItem($item);

        self::$DI['app']['orm.em']->persist($item);

        $item = new FeedItem();
        $item->setEntry($entry)
            ->setOrd(4)
            ->setRecordId(123456789)
            ->setSbasId(123456789);
        $entry->addItem($item);
        self::$DI['app']['orm.em']->persist($item);

        self::$DI['app']['orm.em']->persist($entry);
        self::$DI['app']['orm.em']->flush();

        $this->assertCount(3, self::$DI['app']['orm.em']->getRepository('Phraseanet:FeedItem')->loadLatest(self::$DI['app'], 20));
    }
}
