<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_Entry_ItemTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     *
     * @var Feed_Entry_Item
     */
    protected static $object;

    /**
     *
     * @var Feed_Entry_Adapter
     */
    protected static $entry;

    /**
     *
     * @var Feed_Adapter
     */
    protected static $feed;
    protected static $feed_title = 'Feed test';
    protected static $feed_subtitle = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?';
    protected static $title = 'entry title';
    protected static $subtitle = 'subtitle lalalala';
    protected static $author_name = 'Jean Bonno';
    protected static $author_email = 'Jean@bonno.fr';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $auth = new Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        self::$feed = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], self::$feed_title, self::$feed_subtitle);
        $publisher = Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], self::$feed, self::$DI['user']);
        self::$entry = Feed_Entry_Adapter::create(self::$DI['app'], self::$feed, $publisher, self::$title, self::$subtitle, self::$author_name, self::$author_email);

        self::$object = Feed_Entry_Item::create($appbox, self::$entry, self::$DI['record_1']);
    }

    public static function tearDownAfterClass()
    {
        self::$feed->delete();
        parent::tearDownAfterClass();
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int(self::$object->get_id()));
    }

    public function testGet_record()
    {
        $this->assertInstanceOf('record_adapter', self::$object->get_record());
        $this->assertEquals(self::$DI['record_1']->get_record_id(), self::$object->get_record()->get_record_id());
        $this->assertEquals(self::$DI['record_1']->get_sbas_id(), self::$object->get_record()->get_sbas_id());
        $this->assertEquals(self::$DI['record_1']->get_base_id(), self::$object->get_record()->get_base_id());
    }

    public function testGet_ord()
    {
        $this->assertTrue(is_int(self::$object->get_ord()));
    }

    public function testGet_entry()
    {
        $this->assertInstanceOf('Feed_Entry_Adapter', self::$object->get_entry());
        $this->assertEquals(self::$entry->get_id(), self::$object->get_entry()->get_id());
    }
}
