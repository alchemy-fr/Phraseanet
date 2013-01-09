<?php

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Border\Attribute\AttributeInterface;

require_once __DIR__ . '/../../../PhraseanetWebTestCaseAuthenticatedAbstract.class.inc';

class ManagerTest extends \PhraseanetWebTestCaseAuthenticatedAbstract
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
        $this->object = new Manager(self::$DI['app']);
        $this->session = new \Entities\LazaretSession();

        self::$DI['app']['EM']->persist($this->session);
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
     * @covers Alchemy\Phrasea\Border\Manager::createLazaret
     */
    public function testProcess()
    {

        $records = array();

        $postProcessRecord = function($record) use (&$records) {
                $records[] = $record;
            };

        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcessRecord));
        $shaChecker = new Checker\Sha256(self::$DI['app']);
        $this->object->registerChecker($shaChecker);

        $phpunit = $this;

        $postProcess = function($element, $visa, $code) use ($phpunit, &$records) {
                $phpunit->assertInstanceOf('\\Entities\\LazaretFile', $element);
                $phpunit->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);
                $phpunit->assertEquals(Manager::LAZARET_CREATED, $code);
                $records[] = $element;
            };

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcess));

        $postProcess = function($element, $visa, $code) use ($phpunit, &$records) {
                $phpunit->assertInstanceOf('\\record_adapter', $element);
                $phpunit->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);
                $phpunit->assertEquals(Manager::RECORD_CREATED, $code);
                $records[] = $element;
            };

        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcess, Manager::FORCE_RECORD));

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

        $postProcessRecord = function($record) use (&$records) {
                $records[] = $record;
            };
        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), NULL, Manager::FORCE_LAZARET));
        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcessRecord));

        foreach ($records as $record) {
            if ($record instanceof \record_adapter) {
                $record->delete();
            }
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::createRecord
     */
    public function testCreateRecord()
    {
        $records = array();

        $postProcessRecord = function($record) use (&$records) {
                $records[] = $record;
            };

        $file = File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']);
        $first = $odd = false;
        $tofetch = array();
        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $databox_field) {
            if ($databox_field->is_readonly()) {
                continue;
            }

            if ($databox_field->is_on_error() || !$databox_field->get_tag()->getTagname()) {
                continue;
            }

            if ($databox_field->is_multi()) {

                $data = array('a', 'Hello Multi ' . $databox_field->get_tag()->getTagname());
                $tofetch [$databox_field->get_name()] = $data;

                $data[] = null;
                $value = new \PHPExiftool\Driver\Value\Multi($data);

                $file->addAttribute(new Attribute\Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
            } else {

                $data = array('Hello Mono ' . $databox_field->get_tag()->getTagname());

                if (!$first) {
                    if ($odd) {
                        $value = new \PHPExiftool\Driver\Value\Mono(current($data));
                        $tofetch [$databox_field->get_name()] = $data;

                        $file->addAttribute(new Attribute\Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
                    } else {
                        $value = new \PHPExiftool\Driver\Value\Mono(current($data));
                        $tofetch [$databox_field->get_name()] = $data;

                        $file->addAttribute(new Attribute\MetaField($databox_field, array(current($data))));
                    }
                }
                if ($first) {
                    $value = new \PHPExiftool\Driver\Value\Mono(null);
                    $first = false;

                    $file->addAttribute(new Attribute\Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
                }
            }

            $odd = !$odd;
        }

        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);
        $file->addAttribute(new Attribute\Story($story));

        $status = '0';
        foreach (range(1, 64) as $i) {
            if ($i == 5) {
                $status .= '1';
            } else {
                $status .= '0';
            }
        }

        $file->addAttribute(new Attribute\Status(self::$DI['app'], $status));

        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, $file, $postProcessRecord, Manager::FORCE_RECORD));

        $record = current($records);

        $found = false;

        foreach ($record->get_grouping_parents()->get_elements() as $parent_story) {
            if ($parent_story->get_serialize_key() === $story->get_serialize_key()) {
                $found = true;
            }
        }

        if (!$found) {
            $this->fail('Unable to find story in parents');
        }

        $this->assertEquals(64, strlen($record->get_status()));
        $this->assertEquals('1', substr($record->get_status(), 0, 1));

        foreach ($tofetch as $name => $values) {

            $found = array();
            foreach ($record->get_caption()->get_field($name)->get_values() as $value) {
                $found[] = $value->getValue();
            }
            $this->assertEquals($values, $found);
        }

        foreach ($records as $record) {
            if ($record instanceof \record_adapter) {
                $record->delete();
            }
        }
        $story->delete();
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::createLazaret
     */
    public function testCreateLazaret()
    {
        $lazaret = null;

        $postProcessRecord = function($element) use (&$lazaret) {
                $lazaret = $element;
            };

        $file = File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']);
        $odd = false;
        $tofetchMeta = $tofetchField = array();
        foreach (self::$DI['collection']->get_databox()->get_meta_structure() as $databox_field) {
            if ($databox_field->is_readonly()) {
                continue;
            }

            if ($databox_field->is_on_error() || !$databox_field->get_tag()->getTagname()) {
                continue;
            }

            if ($databox_field->is_multi()) {

                $data = array('a', 'Hello Multi ' . $databox_field->get_tag()->getTagname());
                $tofetchMeta [$databox_field->get_tag()->getTagname()] = $data;

                $value = new \PHPExiftool\Driver\Value\Multi($data);

                $file->addAttribute(new Attribute\Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
            } else {

                $data = array('Hello Mono ' . $databox_field->get_tag()->getTagname());

                if ($odd) {
                    $value = new \PHPExiftool\Driver\Value\Mono(current($data));
                    $tofetchMeta [$databox_field->get_tag()->getTagname()] = $data;

                    $file->addAttribute(new Attribute\Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
                } else {
                    $tofetchField [$databox_field->get_name()] = $data;

                    $file->addAttribute(new Attribute\MetaField($databox_field, array(current($data))));
                }
            }

            $odd = !$odd;
        }

        $file->addAttribute(new Attribute\Story(self::$DI['record_story_1']));

        $status = '1';
        foreach (range(1, 63) as $i) {
            $status .= '0';
        }

        $file->addAttribute(new Attribute\Status(self::$DI['app'], $status));

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, $file, $postProcessRecord, Manager::FORCE_LAZARET));

        $story_found = $status_found = false;

        $foundMeta = $foundField = array();

        /* @var $lazaret \Entities\LazaretFile */
        foreach ($lazaret->getAttributes() as $attr) {
            $attribute = Attribute\Factory::getFileAttribute(self::$DI['app'], $attr->getName(), $attr->getValue());

            if ($attribute->getName() == AttributeInterface::NAME_STORY) {
                if ($attribute->getValue()->get_serialize_key() == self::$DI['record_story_1']->get_serialize_key()) {
                    $story_found = true;
                }
            } elseif ($attribute->getName() == AttributeInterface::NAME_METADATA) {

                $tagname = $attribute->getValue()->getTag()->getTagname();

                if (!isset($foundMeta[$tagname])) {
                    $foundMeta[$tagname] = array();
                }

                $foundMeta[$tagname] = array_merge($foundMeta[$tagname], $attribute->getValue()->getValue()->asArray());
            } elseif ($attribute->getName() == AttributeInterface::NAME_METAFIELD) {

                $fieldname = $attribute->getField()->get_name();

                if (!isset($foundField[$fieldname])) {
                    $foundField[$fieldname] = array();
                }

                $foundField[$fieldname] = array_merge($foundField[$fieldname], (array) $attribute->getValue());
            } elseif ($attribute->getName() == AttributeInterface::NAME_STATUS) {
                $status_found = $attribute->getValue();
            }
        }

        if (!$story_found) {
            $this->fail('Story is not found');
        }

        if (!$status_found) {
            $this->fail('Status is not found');
        }

        $this->assertEquals(64, strlen($status_found));
        $this->assertEquals('1', substr($status_found, 0, 1));

        foreach ($tofetchField as $name => $values) {

            $this->assertEquals($values, $foundField[$name]);
        }

        foreach ($tofetchMeta as $name => $values) {

            $this->assertEquals($values, $foundMeta[$name]);
        }
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::process
     */
    public function testLazaretAttributes()
    {
        $file = File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']);

        $objectNameTag = new \PHPExiftool\Driver\Tag\IPTC\ObjectName();
        $monoValue = new \PHPExiftool\Driver\Value\Mono('title');
        $monoData = new \PHPExiftool\Driver\Metadata\Metadata($objectNameTag, $monoValue);

        $personInImageTag = new \PHPExiftool\Driver\Tag\XMPIptcExt\PersonInImage();
        $multiValue = new \PHPExiftool\Driver\Value\Multi(array('Babar', 'Celeste'));
        $multiData = new \PHPExiftool\Driver\Metadata\Metadata($personInImageTag, $multiValue);

        $file->addAttribute(new Attribute\Metadata($monoData));
        $file->addAttribute(new Attribute\Metadata($multiData));

        $phpunit = $this;
        $application = self::$DI['app'];

        $postProcess = function($element, $visa, $code) use ($phpunit, $application) {
                $phpunit->assertInstanceOf('\\Entities\\LazaretFile', $element);

                /* @var $element \Entities\LazaretFile */
                foreach ($element->getAttributes() as $attribute) {
                    $phpunit->assertEquals('metadata', $attribute->getName());
                    $value = Attribute\Factory::getFileAttribute($application, $attribute->getName(), $attribute->getValue());
                    $phpunit->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Metadata', $value);
                }
            };

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, $file, $postProcess, Manager::FORCE_LAZARET));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::addMediaAttributes
     */
    public function testAddMediaAttributesPDF()
    {
        $manager = new ManagerTester(self::$DI['app']);

        if (null === self::$DI['app']['xpdf.pdf2text']) {
            $this->markTestSkipped('Pdf To Text could not be instantiate');
        }

        $manager->setPdfToText(self::$DI['app']['xpdf.pdf2text']);

        $file = File::buildFromPathfile(__DIR__ . '/../../../testfiles/HelloWorld.pdf', self::$DI['collection'], self::$DI['app']);

        $count = count($file->getAttributes());
        $manager->addMediaAttributesTester($file);

        $count = count($file->getAttributes());

        $toFound = array(
            'Phraseanet:tf-width',
            'Phraseanet:tf-height',
            'Phraseanet:tf-bits',
            'Phraseanet:tf-channels',
            'Phraseanet:tf-duration',
            'Phraseanet:tf-mimetype',
            'Phraseanet:tf-filename',
            'Phraseanet:pdf-text',
            'Phraseanet:tf-basename',
            'Phraseanet:tf-extension',
            'Phraseanet:tf-size',
        );

        foreach ($file->getAttributes() as $attribute) {
            if ($attribute->getName() == AttributeInterface::NAME_METADATA) {
                $tagname = $attribute->getValue()->getTag()->getTagname();
                if (in_array($tagname, $toFound)) {
                    $previousC = count($toFound);
                    $tmp = array();
                    foreach ($toFound as $val) {
                        if ($tagname != $val) {
                            $tmp[] = $val;
                        }
                    }
                    $toFound = $tmp;
                    $this->assertEquals($previousC - 1, count($toFound));
                }
            }
        }

        $this->assertEquals(array('Phraseanet:tf-duration'), $toFound);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::addMediaAttributes
     */
    public function testAddMediaAttributesAudio()
    {
        $manager = new ManagerTester(self::$DI['app']);

        $file = File::buildFromPathfile(__DIR__ . '/../../../testfiles/test012.wav', self::$DI['collection'], self::$DI['app']);

        $count = count($file->getAttributes());
        $manager->addMediaAttributesTester($file);

        $count = count($file->getAttributes());

        $toFound = array(
            'Phraseanet:tf-width',
            'Phraseanet:tf-height',
            'Phraseanet:tf-bits',
            'Phraseanet:tf-channels',
            'Phraseanet:tf-duration',
            'Phraseanet:tf-mimetype',
            'Phraseanet:tf-filename',
            'Phraseanet:tf-basename',
            'Phraseanet:tf-extension',
            'Phraseanet:tf-size',
        );

        foreach ($file->getAttributes() as $attribute) {
            if ($attribute->getName() == AttributeInterface::NAME_METADATA) {
                $tagname = $attribute->getValue()->getTag()->getTagname();
                if (in_array($tagname, $toFound)) {
                    $previousC = count($toFound);
                    $tmp = array();
                    foreach ($toFound as $val) {
                        if ($tagname != $val) {
                            $tmp[] = $val;
                        }
                    }
                    $toFound = $tmp;
                    $this->assertEquals($previousC - 1, count($toFound));
                }
            }
        }

        $this->assertEquals(array('Phraseanet:tf-width', 'Phraseanet:tf-height', 'Phraseanet:tf-bits', 'Phraseanet:tf-channels'), $toFound);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::addMediaAttributes
     */
    public function testAddMediaAttributes()
    {
        $manager = new ManagerTester(self::$DI['app']);

        $file = File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']);

        $count = count($file->getAttributes());
        $manager->addMediaAttributesTester($file);

        $count = count($file->getAttributes());

        $toFound = array(
            'Phraseanet:tf-width',
            'Phraseanet:tf-height',
            'Phraseanet:tf-bits',
            'Phraseanet:tf-channels',
            'Phraseanet:tf-duration',
            'Phraseanet:tf-mimetype',
            'Phraseanet:tf-filename',
            'Phraseanet:tf-basename',
            'Phraseanet:tf-extension',
            'Phraseanet:tf-size',
        );

        foreach ($file->getAttributes() as $attribute) {
            if ($attribute->getName() == AttributeInterface::NAME_METADATA) {
                $tagname = $attribute->getValue()->getTag()->getTagname();
                if (in_array($tagname, $toFound)) {
                    $previousC = count($toFound);
                    $tmp = array();
                    foreach ($toFound as $val) {
                        if ($tagname != $val) {
                            $tmp[] = $val;
                        }
                    }
                    $toFound = $tmp;
                    $this->assertEquals($previousC - 1, count($toFound));
                }
            }
        }

        $this->assertEquals(array('Phraseanet:tf-duration'), $toFound);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::getVisa
     */
    public function testGetVisa()
    {
        $records = array();

        $postProcessRecord = function($record) use (&$records) {
                $records[] = $record;
            };

        $visa = $this->object->getVisa(File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);

        $this->assertTrue($visa->isValid());

        $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcessRecord);

        $visa = $this->object->getVisa(File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']));

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);

        $this->assertTrue($visa->isValid());

        $this->object->registerChecker(new Checker\Sha256(self::$DI['app']));

        $visa = $this->object->getVisa(File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']));

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

        $shaChecker = new Checker\Sha256(self::$DI['app']);
        $this->object->registerChecker($shaChecker);
        $uuidChecker = new Checker\UUID(self::$DI['app']);
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

        $shaChecker = new Checker\Sha256(self::$DI['app']);
        $uuidChecker = new Checker\UUID(self::$DI['app']);
        $this->object->registerCheckers(array($shaChecker, $uuidChecker));

        $this->assertEquals(array($shaChecker, $uuidChecker), $this->object->getCheckers());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::unregisterChecker
     */
    public function testUnregisterChecker()
    {
        $this->assertEquals(array(), $this->object->getCheckers());

        $shaChecker = new Checker\Sha256(self::$DI['app']);
        $uuidChecker = new Checker\UUID(self::$DI['app']);
        $filenameChecker = new Checker\Filename(self::$DI['app']);
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
        $manager = new ManagerTester(self::$DI['app']);

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

    public function addMediaAttributesTester($file)
    {
        return parent::addMediaAttributes($file);
    }
}
