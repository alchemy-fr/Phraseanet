<?php

use Alchemy\Phrasea\Core\Configuration;

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_AggregateTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var Feed_Aggregate
     */
    protected static $object;
    protected static $feeds;
    protected static $title = 'Feed test';
    protected static $subtitle = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt. Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem. Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur? Quis autem vel eum iure reprehenderit qui in ea voluptate velit esse quam nihil molestiae consequatur, vel illum qui dolorem eum fugiat quo voluptas nulla pariatur?';

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = self::$DI['app']['phraseanet.appbox'];
        $auth = new Session_Authentication_None(self::$DI['user']);
        self::$DI['app']->openAccount($auth);
        $objects[] = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], self::$title, self::$subtitle);
        $objects[] = Feed_Adapter::create(self::$DI['app'], self::$DI['user'], self::$title, self::$subtitle);

        self::$feeds = $objects;
        self::$object = new Feed_Aggregate(self::$DI['app'], self::$feeds);
    }

    public static function tearDownAfterClass()
    {
        foreach (self::$feeds as $feed) {
            $feed->delete();
        }
        parent::tearDownAfterClass();
    }

    public function testGet_icon_url()
    {
        $this->assertEquals('/skins/icons/rss32.gif', self::$object->get_icon_url());
    }

    public function testIs_aggregated()
    {
        $this->assertTrue(self::$object->is_aggregated());
    }

    public function testGet_entries()
    {
        $entries_coll = self::$object->get_entries(0, 5);
        $this->assertInstanceOf('Feed_Entry_Collection', $entries_coll);
        $this->assertEquals(0, count($entries_coll->get_entries()));
    }

    public function testGet_count_total_entries()
    {
        $this->assertEquals(0, self::$object->get_count_total_entries());
    }

    public function testGet_homepage_link()
    {
        $registry = self::$DI['app']['phraseanet.registry'];
        $link = self::$object->get_homepage_link($registry, Feed_Adapter::FORMAT_ATOM);
        $this->assertInstanceOf('Feed_Link', $link);
        $this->assertEquals($registry->get('GV_ServerName') . 'feeds/aggregated/atom/', $link->get_href());
    }

    public function testGet_user_link()
    {
        $registry = self::$DI['app']['phraseanet.registry'];

        $link = self::$object->get_user_link($registry, self::$DI['user'], Feed_Adapter::FORMAT_ATOM);
        $supposed = '/feeds\/userfeed\/aggregated\/([a-zA-Z0-9]{12})\/atom\//';

        $atom = $link->get_href();

        $this->assertRegExp($supposed, str_replace($registry->get('GV_ServerName'), '', $atom));
        $this->assertEquals($atom, self::$object->get_user_link($registry, self::$DI['user'], Feed_Adapter::FORMAT_ATOM)->get_href());
        $this->assertEquals($atom, self::$object->get_user_link($registry, self::$DI['user'], Feed_Adapter::FORMAT_ATOM)->get_href());

        $this->assertNotEquals($atom, self::$object->get_user_link($registry, self::$DI['user'], Feed_Adapter::FORMAT_ATOM, null, true)->get_href());

        $link = self::$object->get_user_link($registry, self::$DI['user'], Feed_Adapter::FORMAT_RSS);
        $supposed = '/feeds\/userfeed\/aggregated\/([a-zA-Z0-9]{12})\/rss\//';
        $this->assertRegExp($supposed, str_replace($registry->get('GV_ServerName'), '', $link->get_href()));
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_created_on());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_updated_on());
    }
}
