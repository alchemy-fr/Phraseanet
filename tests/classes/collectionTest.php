<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;

/**
 * @group functional
 * @group legacy
 */
class collectionTest extends \PhraseanetTestCase
{
    /**
     * @var collection
     */
    private static $object;
    /**
     * @var collection
     */
    private static $objectDisable;

    public function setUp()
    {
        parent::setup();

        if (!self::$object) {
            if (0 === count($databoxes = self::$DI['app']->getDataboxes())) {
                $this->fail('No databox found for collection test');
            }

            $databox = array_shift($databoxes);

            self::$object = collection::create(
                self::$DI['app'],
                $databox,
                self::$DI['app']['phraseanet.appbox'],
                'test_collection',
                self::$DI['user']
            );

            self::$objectDisable = collection::create(
                self::$DI['app'],
                $databox,
                self::$DI['app']['phraseanet.appbox'],
                'test_collection',
                self::$DI['user']
            );

            self::$objectDisable->disable(self::$DI['app']['phraseanet.appbox']);
        }
    }

    public static function tearDownAfterClass()
    {
        if (self::$object instanceof \collection) {
            self::$object->delete();
        }
        self::$object = self::$objectDisable = null;
        parent::tearDownAfterClass();
    }

    public function testDisable()
    {
        $base_id = self::$object->get_base_id();
        $coll_id = self::$object->get_coll_id();
        self::$object->disable(self::$DI['app']['phraseanet.appbox']);
        $this->assertTrue(is_int(self::$object->get_base_id()));
        $this->assertTrue(is_int(self::$object->get_coll_id()));
        $this->assertFalse(self::$object->is_active());

        $sbas_id = self::$object->get_databox()->get_sbas_id();
        $databox = self::$DI['app']->findDataboxById($sbas_id);

        foreach ($databox->get_collections() as $collection) {
            $this->assertTrue($collection->get_base_id() !== $base_id);
            $this->assertTrue($collection->get_coll_id() !== $coll_id);
        }
    }

    public function testEnable()
    {
        self::$objectDisable->enable(self::$DI['app']['phraseanet.appbox']);
        $this->assertTrue(is_int(self::$objectDisable->get_base_id()));
        $this->assertTrue(is_int(self::$objectDisable->get_coll_id()));
        $this->assertTrue(self::$objectDisable->is_active());

        $n = $m = 0;
        foreach (self::$objectDisable->get_databox()->get_collections() as $collection) {
            if ($collection->get_base_id() === self::$objectDisable->get_base_id())
                $n ++;
            if ($collection->get_coll_id() === self::$objectDisable->get_coll_id())
                $m ++;
        }
        $this->assertEquals(1, $n);
        $this->assertEquals(1, $m);
    }

    public function testGet_record_amount()
    {
        self::$object->empty_collection();
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), self::$object);
        record_adapter::createFromFile($file, self::$DI['app']);
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
        $this->assertInstanceOf('Doctrine\DBAL\Driver\Connection', self::$object->get_connection());
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

    public function testSet_label()
    {
        self::$object->set_name('pretty name');
        self::$object->set_label('fr', 'french label');
        self::$object->set_label('en', 'english label');
        self::$object->set_label('nl', null);
        self::$object->set_label('de', null);
        $this->assertEquals('french label', self::$object->get_label('fr'));
        $this->assertEquals('english label', self::$object->get_label('en'));
        $this->assertEquals('pretty name', self::$object->get_label('nl'));
        $this->assertEquals('pretty name', self::$object->get_label('de'));
        $this->assertNull(self::$object->get_label('nl', false));
        $this->assertNull(self::$object->get_label('de', false));
    }

    public function testGet_record_details()
    {
        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . '/../files/cestlafete.jpg'), self::$object);
        $record = record_adapter::createFromFile($file, self::$DI['app']);
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
        $pathfile = new \SplFileInfo(__DIR__ . '/../files/logocoll.gif');
        self::$object->update_logo($pathfile);
        $this->assertEquals(file_get_contents($pathfile->getPathname()), self::$object->get_binary_minilogos());
    }

    public function testGet_from_coll_id()
    {
        $temp_coll = collection::getByCollectionId(self::$DI['app'], self::$object->get_databox(), self::$object->get_coll_id());
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

    public function testGet_name()
    {
        $this->assertTrue(is_string(self::$object->get_name()));
        $this->assertTrue(trim(strip_tags(self::$object->get_name())) === self::$object->get_name());
    }

    /**
     * @dataProvider collectionXmlConfiguration
     */
    public function testIsRegistrationEnabled($data, $value)
    {
        $mock = $this->getMockBuilder('\collection')
            ->disableOriginalConstructor()
            ->setMethods(['get_prefs'])
            ->getMock();

        $mock->expects($this->once())->method('get_prefs')->will($this->returnValue($data));
        $this->assertEquals($value, $mock->isRegistrationEnabled());
    }

    public function collectionXmlConfiguration()
    {
        $xmlInscript =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs><caninscript>1</caninscript>1</baseprefs>
XML;
        $xmlNoInscript =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs><caninscript>0</caninscript>1</baseprefs>
XML;
        $xmlNoInscriptEmpty =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs><caninscript></caninscript></baseprefs>
XML;

        return [
            [$xmlInscript, true],
            [$xmlNoInscript, false],
            [$xmlNoInscriptEmpty, false],
        ];
    }
}
