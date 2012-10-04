<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_Entry_CollectionTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Feed_Entry_Collection
     */
    protected static $object;

    /**
     *
     * @var Feed_Entry_Item
     */
    protected static $item;

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
        $auth = new Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);

        self::$feed = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], self::$feed_title, self::$feed_subtitle);
        $publisher = Feed_Publisher_Adapter::getPublisher(self::$DI['app']['phraseanet.appbox'], self::$feed, self::$DI['user']);
        self::$entry = Feed_Entry_Adapter::create(self::$DI['app'], self::$feed, $publisher, self::$title, self::$subtitle, self::$author_name, self::$author_email);

        self::$item = Feed_Entry_Item::create(self::$DI['app']['phraseanet.appbox'], self::$entry, self::$DI['record_1']);

        self::$object = new Feed_Entry_Collection();
    }

    public static function tearDownAfterClass()
    {
        self::$feed->delete();
        parent::tearDownAfterClass();
    }

    public function testAdd_entry()
    {
        self::$object->add_entry(self::$entry);
    }

    public function testGet_entries()
    {
        $this->assertTrue(is_array(self::$object->get_entries()));
        $this->assertTrue(count(self::$object->get_entries()) == 1);
    }
}
