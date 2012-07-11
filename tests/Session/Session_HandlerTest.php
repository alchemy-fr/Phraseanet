<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class Session_HandlerTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Session_Handler
     */
    protected $object;

    public function setUp()
    {
        parent::setUp();
        $this->object = Session_Handler::getInstance(appbox::get_instance(\bootstrap::getCore()));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        if ($this->object->is_authenticated()) {
            $this->object->logout();
        }

        parent::tearDown();
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf('Session_Handler', $this->object);
    }

    /**
     * @todo Implement testLogout().
     */
    public function testLogout()
    {
        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $databoxes = $user->ACL()->get_granted_sbas();

        $this->assertFalse($this->object->is_authenticated());
        $this->object->authenticate($auth);
        $this->assertTrue($this->object->is_authenticated());
        $this->assertTrue(is_int($this->object->get_ses_id()));

        $ses_id = $this->object->get_ses_id();

        $conn = connection::getPDOConnection();
        $sql = 'SELECT session_id FROM cache WHERE session_id = :ses_id';
        $params = array(':ses_id' => $ses_id);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        $this->assertEquals(1, $stmt->rowCount());

        $loggers = array();

        foreach ($databoxes as $databox) {
            $logger = $this->object->get_logger($databox);
            $this->assertInstanceOf('Session_Logger', $logger);
            $loggers[$databox->get_sbas_id()] = $logger;
        }

        $this->object->logout();
        $this->assertFalse($this->object->is_authenticated());

        $stmt->execute($params);
        $this->assertEquals(0, $stmt->rowCount());
        $stmt->closeCursor();


        foreach ($databoxes as $databox) {

            $logger = $this->object->get_logger($databox);
            $this->assertInstanceOf('Session_Logger', $logger);
            $this->assertNotEquals($loggers[$databox->get_sbas_id()]->get_id(), $logger->get_id());
        }
    }

    /**
     * @todo Implement testStorage().
     */
    public function testStorage()
    {
        $this->assertInstanceOf('Session_Storage_Interface', $this->object->storage());
    }

    /**
     * @todo Implement testClose_storage().
     */
    public function testClose_storage()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_locale().
     */
    public function testGet_locale()
    {
        $this->assertRegExp('/[a-z]{2}_[A-Z]{2}/', Session_Handler::get_locale());
    }

    public function testSet_locale()
    {
        Session_Handler::set_locale('fr_FR');
        $this->assertEquals('fr_FR', Session_Handler::get_locale());
        Session_Handler::set_locale('en_GB');
        $this->assertEquals('en_GB', Session_Handler::get_locale());
    }

    public function testGet_l10n()
    {
        Session_Handler::set_locale('fr_FR');
        $this->assertEquals('FR', $this->object->get_l10n());
        Session_Handler::set_locale('en_GB');
        $this->assertEquals('GB', $this->object->get_l10n());
    }

    public function testGet_I18n()
    {
        Session_Handler::set_locale('fr_FR');
        $this->assertEquals('fr', $this->object->get_I18n());
        Session_Handler::set_locale('en_GB');
        $this->assertEquals('en', $this->object->get_I18n());
    }

    public function testIs_authenticated()
    {
        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $this->assertFalse($this->object->is_authenticated());
        $this->object->authenticate($auth);
        $this->assertTrue($this->object->is_authenticated());
        $this->object->logout();
        $this->assertFalse($this->object->is_authenticated());
    }

    public function testGet_usr_id()
    {
        $this->assertNull($this->object->get_usr_id());

        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $this->object->authenticate($auth);

        $this->assertTrue(is_int($this->object->get_usr_id()));
        $this->assertEquals($user->get_id(), $this->object->get_usr_id());

        $this->object->logout();
        $this->assertNull($this->object->get_usr_id());
    }

    public function testGet_ses_id()
    {
        $this->assertNull($this->object->get_ses_id());

        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $this->object->authenticate($auth);

        $this->assertTrue(is_int($this->object->get_ses_id()));

        $this->object->logout();
        $this->assertNull($this->object->get_ses_id());
    }

    public function testSet_session_prefs()
    {
        $datas = array('bla' => 1, 2     => 'boum');
        $this->object->set_session_prefs('test', $datas);
        $this->assertEquals($datas, $this->object->get_session_prefs('test'));
    }

    public function testGet_session_prefs()
    {
        $this->testSet_session_prefs();
    }

    /**
     * @todo Implement testGet_cookie().
     */
    public function testGet_cookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testSet_cookie().
     */
    public function testSet_cookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testIsset_cookie().
     */
    public function testIsset_cookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testOpen_phrasea_session()
    {
        try {
            $this->object->open_phrasea_session();
            $this->fail();
        } catch (Exception_Session_Closed $e) {

        }

        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $this->object->authenticate($auth);

        $this->object->open_phrasea_session();

        $this->object->logout();

        try {
            $this->object->open_phrasea_session();
            $this->fail();
        } catch (Exception_Session_Closed $e) {

        }
    }

    public function testRestore()
    {
        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $this->object->authenticate($auth);

        $ses_id = $this->object->get_ses_id();
        $this->object->storage()->reset();

        $this->assertFalse($this->object->is_authenticated());

        $this->object->restore($user, $ses_id);

        $this->assertTrue($this->object->is_authenticated());

        $databoxes = $user->ACL()->get_granted_sbas();
        foreach ($databoxes as $databox) {
            $this->assertInstanceOf('Session_Logger', $this->object->get_logger($databox));
        }
    }

    public function testAuthenticate()
    {
        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $this->object->authenticate($auth);

        $registry = registry::get_instance();


        foreach ($user->ACL()->get_granted_sbas() as $databox) {
            $sql = 'SELECT usr_id FROM collusr WHERE site = :site AND usr_id = :usr_id AND coll_id = :coll_id';
            $stmt = $databox->get_connection()->prepare($sql);

            foreach ($user->ACL()->get_granted_base(array(), array($databox->get_sbas_id())) as $collection) {
                $stmt->execute(array(':site'    => $registry->get('GV_sit'), ':usr_id'  => $user->get_id(), ':coll_id' => $collection->get_coll_id()));
                $this->assertEquals(1, $stmt->rowCount());
            }

            $stmt->closeCursor();
        }

        $this->object->logout();
    }

    /**
     * @todo Implement testAdd_persistent_cookie().
     */
    public function testAdd_persistent_cookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_logger()
    {
        $user = self::$user;
        $auth = new Session_Authentication_None($user);
        $databoxes = $user->ACL()->get_granted_sbas();

        $this->object->authenticate($auth);
        $this->assertTrue($this->object->is_authenticated());

        foreach ($databoxes as $databox) {
            $this->assertInstanceOf('Session_Logger', $this->object->get_logger($databox));
        }
        $this->assertTrue(is_int($this->object->get_logger($databox)->get_id()));
    }

    /**
     * @todo Implement testGet_my_sessions().
     */
    public function testGet_my_sessions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testSet_event_module().
     */
    public function testSet_event_module()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGet_active_sessions().
     */
    public function testGet_active_sessions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
