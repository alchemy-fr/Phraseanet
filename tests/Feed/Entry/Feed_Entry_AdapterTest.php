<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class Feed_Entry_AdapterTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     *
     * @var Feed_Entry_Adapter
     */
    protected static $object;

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
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $auth = new Session_Authentication_None(self::$user);
        $appbox->get_session()->authenticate($auth);

        self::$feed = Feed_Adapter::create($appbox, self::$user, self::$feed_title, self::$feed_subtitle);
        $publisher = Feed_Publisher_Adapter::getPublisher($appbox, self::$feed, self::$user);
        self::$object = Feed_Entry_Adapter::create($appbox, self::$feed, $publisher, self::$title, self::$subtitle, self::$author_name, self::$author_email);
    }

    public static function tearDownAfterClass()
    {
        self::$feed->delete();
        parent::tearDownAfterClass();
    }

    public function testGet_feed()
    {
        $this->assertInstanceOf('Feed_Adapter', self::$object->get_feed());
        $this->assertEquals(self::$feed->get_id(), self::$object->get_feed()->get_id());
    }

    public function testGet_id()
    {
        $this->assertTrue(is_int(self::$object->get_id()));
    }

    public function testGet_title()
    {
        $this->assertEquals(self::$title, self::$object->get_title());
    }

    public function testGet_subtitle()
    {
        $this->assertEquals(self::$subtitle, self::$object->get_subtitle());
    }

    public function testSet_title()
    {
        $new_titre = 'UNnouveau titre';
        self::$object->set_title($new_titre);
        $this->assertEquals($new_titre, self::$object->get_title());
        $new_titre = '<i>UNnouveau titre encore</i>';
        self::$object->set_title($new_titre);
        $this->assertNotEquals($new_titre, self::$object->get_title());
        $this->assertEquals(strip_tags($new_titre), self::$object->get_title());
        try {
            self::$object->set_title('');
            $this->fail();
        } catch (Exception_InvalidArgument $e) {

        }
    }

    public function testSet_subtitle()
    {
        $new_subtitle = 'PROUT
      ET PROUT';
        self::$object->set_subtitle($new_subtitle);
        $this->assertEquals($new_subtitle, self::$object->get_subtitle());
        $new_subtitle = '';
        self::$object->set_subtitle($new_subtitle);
        $this->assertEquals($new_subtitle, self::$object->get_subtitle());
    }

    public function testSet_author_name()
    {
        $new_author = 'Tintin et Milou';
        self::$object->set_author_name($new_author);
        $this->assertEquals($new_author, self::$object->get_author_name());
        self::$object->set_author_name(self::$author_name);
        $this->assertEquals(self::$author_name, self::$object->get_author_name());
    }

    public function testSet_author_email()
    {
        $new_email = 'Tintin@herge.be';
        self::$object->set_author_email($new_email);
        $this->assertEquals($new_email, self::$object->get_author_email());
        self::$object->set_author_email(self::$author_email);
        $this->assertEquals(self::$author_email, self::$object->get_author_email());
    }

    public function testGet_publisher()
    {
        $this->assertInstanceOf('Feed_Publisher_Adapter', self::$object->get_publisher());
        $this->assertEquals(self::$object->get_publisher()->get_user()->get_id(), self::$user->get_id());
    }

    public function testGet_created_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_created_on());
    }

    public function testGet_updated_on()
    {
        $this->assertInstanceOf('DateTime', self::$object->get_updated_on());
    }

    public function testGet_author_name()
    {
        $this->assertEquals(self::$author_name, self::$object->get_author_name());
    }

    public function testGet_author_email()
    {
        $this->assertEquals(self::$author_email, self::$object->get_author_email());
    }

    public function testGet_content()
    {
        $this->assertTrue(is_array(self::$object->get_content()));
        $this->assertEquals(0, count(self::$object->get_content()));
    }

    public function testLoad_from_id()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $test_entry = Feed_Entry_Adapter::load_from_id($appbox, self::$object->get_id());

        $this->assertInstanceOf('Feed_Entry_Adapter', $test_entry);
        $this->assertEquals(self::$object->get_id(), $test_entry->get_id());
    }
}
