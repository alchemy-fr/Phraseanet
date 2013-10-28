<?php

namespace Doctrine\Tests\Repositories;

use Entities\FtpExport;
use Entities\FtpExportElement;
use Gedmo\Timestampable\TimestampableListener;

class FeedItemRepositoryTest extends \PhraseanetPHPUnitAbstract
{
    public function testIs_record_in_public_feedInPublicFeed()
    {
        $item = $this->insertOneFeedItem(self::$DI['user'], true);
        $record = $item->getRecord(self::$DI['app']);
        $this->assertTrue(self::$DI['app']['EM']->getRepository('Entities\FeedItem')->isRecordInPublicFeed(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id()));
    }

    public function testIs_record_in_public_feedInPrivateFeed()
    {
        $record = $this->insertOneFeedItem(self::$DI['user'], false)->getRecord(self::$DI['app']);
        $this->assertFalse(self::$DI['app']['EM']->getRepository('Entities\FeedItem')->isRecordInPublicFeed(self::$DI['app'], $record->get_sbas_id(), $record->get_record_id()));
    }

    public function testLoadLatestItems()
    {
        $this->insertOneFeedItem(self::$DI['user'], true, 2);
        $this->assertCount(2, self::$DI['app']['EM']->getRepository('Entities\FeedItem')->loadLatest(self::$DI['app'], 20));
    }

    public function testLoadLatestItemsLessItems()
    {
        $this->insertOneFeedItem(self::$DI['user'], true, 2);
        $this->assertCount(1, self::$DI['app']['EM']->getRepository('Entities\FeedItem')->loadLatest(self::$DI['app'], 1));
    }

    public function testLoadLatestItemsNoPublic()
    {
        $this->insertOneFeedItem(self::$DI['user'], false, 2);
        $this->assertCount(0, self::$DI['app']['EM']->getRepository('Entities\FeedItem')->loadLatest(self::$DI['app'], 20));
    }

    public function testLoadLatestWithDeletedDatabox()
    {
        $record = $this->getMockBuilder('record_adapter')
            ->disableOriginalConstructor()
            ->getMock();
        $record->expects($this->any())
            ->method('get_sbas_id')
            ->will($this->returnValue(0));
        $record->expects($this->any())
            ->method('get_record_id')
            ->will($this->returnValue(self::$DI['record_1']->get_record_id()));

        $this->insertOneFeedItem(self::$DI['user'], true, 1, $record);
        $this->assertCount(0, self::$DI['app']['EM']->getRepository('Entities\FeedItem')->loadLatest(self::$DI['app'], 20));
    }

    public function testLoadLatestWithDeletedRecord()
    {
        $record = $this->getMockBuilder('record_adapter')
                  ->disableOriginalConstructor()
                  ->getMock();
        $record->expects($this->any())
            ->method('get_sbas_id')
            ->will($this->returnValue(self::$DI['record_1']->get_sbas_id()));
        $record->expects($this->any())
            ->method('get_record_id')
            ->will($this->returnValue(0));

        $this->insertOneFeedItem(self::$DI['user'], true, 1, $record);
        $this->assertCount(0, self::$DI['app']['EM']->getRepository('Entities\FeedItem')->loadLatest(self::$DI['app'], 20));
    }
}
