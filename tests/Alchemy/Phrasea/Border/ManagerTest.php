<?php

namespace Alchemy\Phrasea\Border;

require_once __DIR__ . '/../../../PhraseanetPHPUnitAbstract.class.inc';

class ManagerTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var Manager
     */
    protected $object;
    protected $session;
    protected static $file1;
    protected static $file2;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $tmpDir = sys_get_temp_dir();

        self::$file1 = $tmpDir . '/test.jpg';
        copy(__DIR__ . '/../../../testfiles/iphone_pic.jpg', self::$file1);

        self::$file2 = $tmpDir . '/test.txt';
        copy(__DIR__ . '/../../../testfiles/ISOLatin1.txt', self::$file2);
    }

    public static function tearDownAfterClass()
    {
        if (file_exists(self::$file1)) {
            unlink(self::$file1);
        }
        if (file_exists(self::$file2)) {
            unlink(self::$file2);
        }
        parent::tearDownAfterClass();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::__construct
     */
    public function setUp()
    {
        parent::setUp();
        $this->object = new Manager(self::$core['EM']);
        $this->session = new \Entities\LazaretSession();

        self::$core['EM']->persist($this->session);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::__destruct
     */
    public function tearDown()
    {
        $this->object = null;
        parent::tearDown();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::process
     */
    public function testProcess()
    {
        $records = array();

        $postProcessRecord = function($record) use(&$records) {
                $records[] = $record;
            };

        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, new File(self::$file1, self::$collection), $postProcessRecord));
        $shaChecker = new Checker\Sha256();
        $this->object->registerChecker($shaChecker);

        $phpunit = $this;

        $postProcess = function($element, $visa, $code) use ($phpunit, &$records) {
                $phpunit->assertInstanceOf('\\Entities\\LazaretFile', $element);
                $phpunit->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);
                $phpunit->assertEquals(Manager::LAZARET_CREATED, $code);
                $records[] = $element;
            };

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, new File(self::$file1, self::$collection), $postProcess));

        $postProcess = function($element, $visa, $code) use ($phpunit, &$records) {
                $phpunit->assertInstanceOf('\\record_adapter', $element);
                $phpunit->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);
                $phpunit->assertEquals(Manager::RECORD_CREATED, $code);
                $records[] = $element;
            };

        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, new File(self::$file1, self::$collection), $postProcess, Manager::FORCE_RECORD));

        foreach ($records as $record) {
            if ($record instanceof \record_adapter) {
                $record->delete();
            }
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::process
     */
    public function testProcessForceLazaret()
    {
        $records = array();

        $postProcessRecord = function($record) use(&$records) {
                $records[] = $record;
            };
        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, new File(self::$file1, self::$collection), NULL, Manager::FORCE_LAZARET));
        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, new File(self::$file1, self::$collection), $postProcessRecord));

        foreach ($records as $record) {
            if ($record instanceof \record_adapter) {
                $record->delete();
            }
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::process
     */
    public function testLazaretAttributes()
    {
        $file = new File(self::$file1, self::$collection);

        $objectNameTag = new \PHPExiftool\Driver\Tag\IPTC\ObjectName();
        $monoValue = new \PHPExiftool\Driver\Value\Mono('title');
        $monoData = new \PHPExiftool\Driver\Metadata\Metadata($objectNameTag, $monoValue);

        $personInImageTag = new \PHPExiftool\Driver\Tag\XMPIptcExt\PersonInImage();
        $multiValue = new \PHPExiftool\Driver\Value\Multi(array('Babar', 'Celeste'));
        $multiData = new \PHPExiftool\Driver\Metadata\Metadata($personInImageTag, $multiValue);

        $file->addAttribute(new Attribute\Metadata($monoData));
        $file->addAttribute(new Attribute\Metadata($multiData));

        $phpunit = $this;

        $postProcess = function($element, $visa, $code) use ($phpunit) {
                $phpunit->assertInstanceOf('\\Entities\\LazaretFile', $element);

                /* @var $element \Entities\LazaretFile */
                foreach ($element->getAttributes() as $attribute) {
                    $phpunit->assertEquals('metadata', $attribute->getName());
                    $value = Attribute\Factory::getFileAttribute($attribute->getName(), $attribute->getValue());
                    $phpunit->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Metadata', $value);
                }
            };

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, $file, $postProcess, Manager::FORCE_LAZARET));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::getVisa
     */
    public function testGetVisa()
    {
        $records = array();

        $postProcessRecord = function($record) use(&$records) {
                $records[] = $record;
            };

        $visa = $this->object->getVisa(new File(self::$file1, self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);

        $this->assertTrue($visa->isValid());

        $this->object->process($this->session, new File(self::$file1, self::$collection), $postProcessRecord);


        $visa = $this->object->getVisa(new File(self::$file1, self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);

        $this->assertTrue($visa->isValid());

        $this->object->registerChecker(new Checker\Sha256());

        $visa = $this->object->getVisa(new File(self::$file1, self::$collection));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);

        $this->assertFalse($visa->isValid());

        foreach ($records as $record) {
            if ($record instanceof \record_adapter) {
                $record->delete();
            }
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::registerChecker
     * @covers Alchemy\Phrasea\Border\Manager::getCheckers
     */
    public function testRegisterChecker()
    {
        $this->assertEquals(array(), $this->object->getCheckers());

        $shaChecker = new Checker\Sha256();
        $this->object->registerChecker($shaChecker);
        $uuidChecker = new Checker\UUID();
        $this->object->registerChecker($uuidChecker);

        $this->assertEquals(array($shaChecker, $uuidChecker), $this->object->getCheckers());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::registerCheckers
     * @covers Alchemy\Phrasea\Border\Manager::getCheckers
     */
    public function testRegisterCheckers()
    {
        $this->assertEquals(array(), $this->object->getCheckers());

        $shaChecker = new Checker\Sha256();
        $uuidChecker = new Checker\UUID();
        $this->object->registerCheckers(array($shaChecker, $uuidChecker));

        $this->assertEquals(array($shaChecker, $uuidChecker), $this->object->getCheckers());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::unregisterChecker
     */
    public function testUnregisterChecker()
    {
        $this->assertEquals(array(), $this->object->getCheckers());

        $shaChecker = new Checker\Sha256();
        $uuidChecker = new Checker\UUID();
        $filenameChecker = new Checker\Filename();
        $this->object->registerCheckers(array($shaChecker, $uuidChecker, $filenameChecker));

        $this->assertEquals(array($shaChecker, $uuidChecker, $filenameChecker), $this->object->getCheckers());

        $this->object->unregisterChecker($uuidChecker);
        $this->assertEquals(array($shaChecker, $filenameChecker), $this->object->getCheckers());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::bookLazaretPathfile
     */
    public function testBookLazaretPathfile()
    {
        $manager = new ManagerTester(self::$core['EM']);

        $file1 = $manager->bookLazaretPathfileTester('babebibobu.txt');
        $file2 = $manager->bookLazaretPathfileTester('babebibobu.txt');

        $this->assertNotEquals($file2, $file1);

        $this->assertTrue(file_exists($file1));
        $this->assertTrue(file_exists($file2));

        unlink($file1);
        unlink($file2);
    }
}

class ManagerTester extends Manager
{

    public function bookLazaretPathfileTester($filename)
    {
        return parent::bookLazaretPathfile($filename);
    }
}
