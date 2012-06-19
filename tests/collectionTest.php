<?php

require_once __DIR__ . '/PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class collectionTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var collection
     */
    protected static $object;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $auth = new Session_Authentication_None(self::$user);
        $appbox->get_session()->authenticate($auth);

        $found = false;
        foreach ($appbox->get_databoxes() as $databox) {
            $found = true;
            break;
        }

        if ( ! $found)
            self::fail('No databox found for collection test');

        self::$object = collection::create($databox, $appbox, 'test_collection', self::$user);

        if ( ! self::$object instanceof collection)
            self::fail('Unable to create collection');
    }

    public static function tearDownAfterClass()
    {

        self::$object->delete();
        parent::tearDownAfterClass();
    }

    public function testEnable()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());
        $base_id = self::$object->get_base_id();
        $coll_id = self::$object->get_coll_id();
        self::$object->disable($appbox);
        $this->assertTrue(is_int(self::$object->get_base_id()));
        $this->assertTrue(is_int(self::$object->get_coll_id()));
        $this->assertFalse(self::$object->is_active());

        $sbas_id = self::$object->get_databox()->get_sbas_id();
        $databox = databox::get_instance($sbas_id);

        foreach ($databox->get_collections() as $collection) {
            $this->assertTrue($collection->get_base_id() !== $base_id);
            $this->assertTrue($collection->get_coll_id() !== $coll_id);
        }

        self::$object->enable($appbox);
        $this->assertTrue(is_int(self::$object->get_base_id()));
        $this->assertTrue(is_int(self::$object->get_coll_id()));
        $this->assertTrue(self::$object->is_active());

        $databox = databox::get_instance($sbas_id);

        $n = $m = 0;
        foreach ($databox->get_collections() as $collection) {
            if ($collection->get_base_id() === $base_id)
                $n ++;
            if ($collection->get_coll_id() === $coll_id)
                $m ++;
        }
        $this->assertEquals($n, 1);
        $this->assertEquals($m, 1);
    }

    public function testDisable()
    {
        $this->testEnable();
    }

    public function testGet_record_amount()
    {
        self::$object->empty_collection();
        $file = new Alchemy\Phrasea\Border\File(self::$core['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/testfiles/cestlafete.jpg')), self::$object);
        record_adapter::createFromFile($file);
        $this->assertTrue(self::$object->get_record_amount() === 1);
        self::$object->empty_collection();
        $this->assertTrue(self::$object->get_record_amount() === 0);
    }

    public function testIs_active()
    {
        $this->assertTrue(is_bool(self::$object->is_active()));
    }

    public function testGet_databox()
    {
        $this->assertInstanceOf('databox', self::$object->get_databox());
    }

    public function testGet_connection()
    {
        $this->assertInstanceOf('connection_pdo', self::$object->get_connection());
    }

    /**
     * @todo Implement testSet_public_presentation().
     */
    public function testSet_public_presentation()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_name()
    {
        self::$object->set_name('babababe bi bo bu');
        $this->assertEquals('babababe bi bo bu', self::$object->get_name());
        self::$object->set_name('babaé&\'" bi bo bu');
        $this->assertEquals('babaé&\'" bi bo bu', self::$object->get_name());
        self::$object->set_name('<i>babababe bi bo bu</i>');
        $this->assertEquals('babababe bi bo bu', self::$object->get_name());
        self::$object->set_name('<strong>babababe bi bo bu');
        $this->assertEquals('babababe bi bo bu', self::$object->get_name());
    }

    public function testGet_record_details()
    {
        $file = new Alchemy\Phrasea\Border\File(self::$core['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/testfiles/cestlafete.jpg')), self::$object);
        $record = record_adapter::createFromFile($file);
        $details = self::$object->get_record_details();

        $this->assertTrue(is_array($details));
        foreach ($details as $detail) {
            $this->assertTrue(is_array($detail));
            $this->assertArrayHasKey('coll_id', $detail);
            $this->asserttrue(is_int($detail['coll_id']));
            $this->assertArrayHasKey('name', $detail);
            $this->asserttrue(is_string($detail['name']));
            $this->assertArrayHasKey('amount', $detail);
            $this->asserttrue(is_int($detail['amount']));
            $this->assertArrayHasKey('size', $detail);
            $this->asserttrue(is_int($detail['size']));
        }
    }

    public function testUpdate_logo()
    {
        $pathfile = new \SplFileInfo(__DIR__ . '/testfiles/logocoll.gif');
        self::$object->update_logo($pathfile);
        $this->assertEquals(file_get_contents($pathfile->getPathname()), self::$object->get_binary_minilogos());
    }

    public function testReset_watermark()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testDelete()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_from_base_id()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_from_coll_id()
    {
        $temp_coll = collection::get_from_coll_id(self::$object->get_databox(), self::$object->get_coll_id());
        $this->assertEquals(self::$object->get_coll_id(), $temp_coll->get_coll_id());
        $this->assertEquals(self::$object->get_base_id(), $temp_coll->get_base_id());
    }

    public function testGet_base_id()
    {
        $this->assertTrue(is_int(self::$object->get_base_id()));
        $this->assertTrue(self::$object->get_base_id() > 0);
    }

    public function testGet_sbas_id()
    {
        $this->assertTrue(is_int(self::$object->get_sbas_id()));
        $this->assertEquals(self::$object->get_sbas_id(), self::$object->get_databox()->get_sbas_id());
    }

    public function testGet_coll_id()
    {
        $this->assertTrue(is_int(self::$object->get_coll_id()));
        $this->assertTrue(self::$object->get_coll_id() > 0);
    }

    /**
     * @todo Implement testGet_prefs().
     */
    public function testGet_prefs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testSet_prefs().
     */
    public function testSet_prefs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_name()
    {
        $this->assertTrue(is_string(self::$object->get_name()));
        $this->assertTrue(trim(strip_tags(self::$object->get_name())) === self::$object->get_name());
    }

    /**
     * @todo Implement testGet_pub_wm().
     */
    public function testGet_pub_wm()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testIs_available().
     */
    public function testIs_available()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testUnmount_collection().
     */
    public function testUnmount_collection()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCreate().
     */
    public function testCreate()
    {

    }

    /**
     * @todo Implement testSet_admin().
     */
    public function testSet_admin()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testMount_collection().
     */
    public function testMount_collection()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetLogo().
     */
    public function testGetLogo()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetWatermark().
     */
    public function testGetWatermark()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetPresentation().
     */
    public function testGetPresentation()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetStamp().
     */
    public function testGetStamp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}
