<?php

namespace Alchemy\Tests\Phrasea\Border;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\Checker\Sha256;
use Alchemy\Phrasea\Border\Checker\Filename;
use Alchemy\Phrasea\Border\Checker\UUID;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Border\Attribute\Factory;
use Alchemy\Phrasea\Border\Attribute\MetaField;
use Alchemy\Phrasea\Border\Attribute\Metadata;
use Alchemy\Phrasea\Border\Attribute\Status;
use Alchemy\Phrasea\Border\Attribute\Story;
use Alchemy\Phrasea\Model\Entities\LazaretFile;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class ManagerTest extends \PhraseanetAuthenticatedWebTestCase
{
    /**
     * @var Manager
     */
    protected $object;
    protected $session;
    private static $file1;
    private static $file2;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $tmpDir = sys_get_temp_dir();

        self::$file1 = $tmpDir . '/test.jpg';
        copy(__DIR__ . '/../../../../files/iphone_pic.jpg', self::$file1);

        self::$file2 = $tmpDir . '/test.txt';
        copy(__DIR__ . '/../../../../files/ISOLatin1.txt', self::$file2);
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
        $this->session = self::$DI['app']['orm.em']->find('Phraseanet:LazaretSession', 1);
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

        $records = [];

        $postProcessRecord = function ($record) use (&$records) {
                $records[] = $record;
            };

        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcessRecord));
        $shaChecker = new Sha256(self::$DI['app']);
        $this->object->registerChecker($shaChecker);

        $postProcess = function ($element, $visa, $code) use (&$records) {
            $this->assertInstanceOf('\\Alchemy\Phrasea\Model\Entities\\LazaretFile', $element);
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);
            $this->assertEquals(Manager::LAZARET_CREATED, $code);
            $records[] = $element;
        };

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, File::buildFromPathfile(self::$file1, self::$DI['collection'], self::$DI['app']), $postProcess));

        $postProcess = function ($element, $visa, $code) use (&$records) {
            $this->assertInstanceOf('\\record_adapter', $element);
            $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Visa', $visa);
            $this->assertEquals(Manager::RECORD_CREATED, $code);
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
        $records = [];

        $postProcessRecord = function ($record) use (&$records) {
                $records[] = $record;
            };
        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
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
        $records = [];

        $postProcessRecord = function ($record) use (&$records) {
            $records[] = $record;
        };

        $app = $this->getApplication();
        $collection = $this->getCollection();
        $file = File::buildFromPathfile(self::$file1, $collection, $app);
        $first = $odd = false;
        $tofetch = [];
        foreach ($collection->get_databox()->get_meta_structure() as $databox_field) {
            if ($databox_field->is_readonly()) {
                continue;
            }

            if ($databox_field->is_on_error() || !$databox_field->get_tag()->getTagname()) {
                continue;
            }

            if ($databox_field->is_multi()) {

                $data = ['a', 'Hello Multi ' . $databox_field->get_tag()->getTagname()];
                $tofetch [$databox_field->get_name()] = $data;

                $data[] = null;
                $value = new \PHPExiftool\Driver\Value\Multi($data);

                $file->addAttribute(new Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
            } else {

                $data = ['Hello Mono ' . $databox_field->get_tag()->getTagname()];

                if (!$first) {
                    if ($odd) {
                        $value = new \PHPExiftool\Driver\Value\Mono(current($data));
                        $tofetch [$databox_field->get_name()] = $data;

                        $file->addAttribute(new Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
                    } else {
                        $value = new \PHPExiftool\Driver\Value\Mono(current($data));
                        $tofetch [$databox_field->get_name()] = $data;

                        $file->addAttribute(new MetaField($databox_field, [current($data)]));
                    }
                }
                if ($first) {
                    $value = new \PHPExiftool\Driver\Value\Mono(null);
                    $first = false;

                    $file->addAttribute(new Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
                }
            }

            $odd = !$odd;
        }

        $story = \record_adapter::createStory($app, $collection);
        $file->addAttribute(new Story($story));

        $status = '';
        foreach (range(0, 31) as $i) {
            if ($i == 4 || $i == 8) {
                $status .= '1';
            } else {
                $status .= '0';
            }
        }

        $file->addAttribute(new Status($app, strrev($status)));

        $app['phraseanet.SE'] = $this->createSearchEngineMock();
        $this->assertEquals(Manager::RECORD_CREATED, $this->object->process($this->session, $file, $postProcessRecord, Manager::FORCE_RECORD));

        /** @var \record_adapter $record */
        $record = current($records);
        $this->assertInstanceOf(\record_adapter::class, $record);

        $found = false;

        foreach ($record->get_grouping_parents()->get_elements() as $parent_story) {
            if ($parent_story->getId() === $story->getId()) {
                $found = true;
            }
        }

        if (!$found) {
            $this->fail('Unable to find story in parents');
        }

        $status = strrev($record->getStatus());

        $this->assertEquals(32, strlen($status));
        $this->assertEquals('1', substr($status, 4, 1));
        $this->assertEquals('1', substr($status, 8, 1));

        foreach ($tofetch as $name => $values) {
            $found = [];

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

        $postProcessRecord = function ($element) use (&$lazaret) {
                $lazaret = $element;
            };

        $app = $this->getApplication();
        $collection = $this->getCollection();
        $file = File::buildFromPathfile(self::$file1, $collection, $app);
        $odd = false;
        $tofetchMeta = $tofetchField = [];
        foreach ($collection->get_databox()->get_meta_structure() as $databox_field) {
            if ($databox_field->is_readonly()) {
                continue;
            }

            if ($databox_field->is_on_error() || !$databox_field->get_tag()->getTagname()) {
                continue;
            }

            if ($databox_field->is_multi()) {

                $data = ['a', 'Hello Multi ' . $databox_field->get_tag()->getTagname()];
                $tofetchMeta [$databox_field->get_tag()->getTagname()] = $data;

                $value = new \PHPExiftool\Driver\Value\Multi($data);

                $file->addAttribute(new Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
            } else {

                $data = ['Hello Mono ' . $databox_field->get_tag()->getTagname()];

                if ($odd) {
                    $value = new \PHPExiftool\Driver\Value\Mono(current($data));

                    if (!isset($tofetchMeta [$databox_field->get_tag()->getTagname()])) {
                        $tofetchMeta [$databox_field->get_tag()->getTagname()] = [];
                    }

                    $tofetchMeta [$databox_field->get_tag()->getTagname()] = array_merge($tofetchMeta [$databox_field->get_tag()->getTagname()], $data);

                    $file->addAttribute(new Metadata(new \PHPExiftool\Driver\Metadata\Metadata($databox_field->get_tag(), $value)));
                } else {
                    $tofetchField [$databox_field->get_name()] = $data;

                    $file->addAttribute(new MetaField($databox_field, [current($data)]));
                }
            }

            $odd = !$odd;
        }

        $story = $this->getRecordStory1();
        $file->addAttribute(new Story($story));

        $status = '1';

        foreach (range(1, 31) as $i) {
            $status .= '0';
        }

        $file->addAttribute(new Status($app, $status));

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, $file, $postProcessRecord, Manager::FORCE_LAZARET));

        $story_found = $status_found = false;

        $foundMeta = $foundField = [];

        /** @var LazaretFile $lazaret */
        foreach ($lazaret->getAttributes() as $attr) {
            $attribute = Factory::getFileAttribute($app, $attr->getName(), $attr->getValue());

            if ($attribute->getName() == AttributeInterface::NAME_STORY) {
                /** @var Story $attribute */
                if ($attribute->getValue()->getId() == $story->getId()) {
                    $story_found = true;
                }
            } elseif ($attribute->getName() == AttributeInterface::NAME_METADATA) {

                $tagname = $attribute->getValue()->getTag()->getTagname();

                if (!isset($foundMeta[$tagname])) {
                    $foundMeta[$tagname] = [];
                }

                $foundMeta[$tagname] = array_merge($foundMeta[$tagname], $attribute->getValue()->getValue()->asArray());
            } elseif ($attribute->getName() == AttributeInterface::NAME_METAFIELD) {

                $fieldname = $attribute->getField()->get_name();

                if (!isset($foundField[$fieldname])) {
                    $foundField[$fieldname] = [];
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

        $this->assertEquals(32, strlen($status_found));
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
        $multiValue = new \PHPExiftool\Driver\Value\Multi(['Babar', 'Celeste']);
        $multiData = new \PHPExiftool\Driver\Metadata\Metadata($personInImageTag, $multiValue);

        $file->addAttribute(new Metadata($monoData));
        $file->addAttribute(new Metadata($multiData));

        $application = $this->getApplication();

        $postProcess = function ($element, $visa, $code) use ($application) {
            $this->assertInstanceOf('\\Alchemy\Phrasea\Model\Entities\\LazaretFile', $element);

            /* @var $element \Alchemy\Phrasea\Model\Entities\LazaretFile */
            foreach ($element->getAttributes() as $attribute) {
                $this->assertEquals('metadata', $attribute->getName());
                $value = Factory::getFileAttribute($application, $attribute->getName(), $attribute->getValue());
                $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Attribute\\Metadata', $value);
            }
        };

        $this->assertEquals(Manager::LAZARET_CREATED, $this->object->process($this->session, $file, $postProcess, Manager::FORCE_LAZARET));
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::addMediaAttributes
     */
    public function testAddMediaAttributesPDF()
    {
        $pdfToText = $this->getMockBuilder('XPDF\PdfToText')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = new ManagerTester(self::$DI['app']);
        self::$DI['app']['phraseanet.metadata-reader']->setPdfToText($pdfToText);

        $pdfToText->expects($this->once())
            ->method('getText')
            ->with(realpath(__DIR__ . '/../../../../files/HelloWorld.pdf'))
            ->will($this->returnValue('text content'));

        $file = File::buildFromPathfile(__DIR__ . '/../../../../files/HelloWorld.pdf', self::$DI['collection'], self::$DI['app']);
        $manager->addMediaAttributesTester($file);

        $toFound = [
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
        ];

        foreach ($file->getAttributes() as $attribute) {
            if ($attribute->getName() == AttributeInterface::NAME_METADATA) {
                $tagname = $attribute->getValue()->getTag()->getTagname();
                if (in_array($tagname, $toFound)) {
                    $previousC = count($toFound);
                    $tmp = [];
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

        $this->assertEquals(['Phraseanet:tf-duration'], $toFound);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::addMediaAttributes
     */
    public function testAddMediaAttributesAudio()
    {
        $manager = new ManagerTester(self::$DI['app']);

        $file = File::buildFromPathfile(__DIR__ . '/../../../../files/audio.wav', self::$DI['collection'], self::$DI['app']);

        $count = count($file->getAttributes());
        $manager->addMediaAttributesTester($file);

        $count = count($file->getAttributes());

        $toFound = [
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
        ];

        foreach ($file->getAttributes() as $attribute) {
            if ($attribute->getName() == AttributeInterface::NAME_METADATA) {
                $tagname = $attribute->getValue()->getTag()->getTagname();
                if (in_array($tagname, $toFound)) {
                    $previousC = count($toFound);
                    $tmp = [];
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

        $this->assertEquals(['Phraseanet:tf-width', 'Phraseanet:tf-height', 'Phraseanet:tf-bits', 'Phraseanet:tf-channels'], $toFound);
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

        $toFound = [
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
        ];

        foreach ($file->getAttributes() as $attribute) {
            if ($attribute->getName() == AttributeInterface::NAME_METADATA) {
                $tagname = $attribute->getValue()->getTag()->getTagname();
                if (in_array($tagname, $toFound)) {
                    $previousC = count($toFound);
                    $tmp = [];
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

        $this->assertEquals(['Phraseanet:tf-duration'], $toFound);
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::registerChecker
     * @covers Alchemy\Phrasea\Border\Manager::getCheckers
     */
    public function testRegisterChecker()
    {
        $this->assertEquals([], $this->object->getCheckers());

        $shaChecker = new Sha256(self::$DI['app']);
        $this->object->registerChecker($shaChecker);
        $uuidChecker = new UUID(self::$DI['app']);
        $this->object->registerChecker($uuidChecker);

        $this->assertEquals([$shaChecker, $uuidChecker], $this->object->getCheckers());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::registerCheckers
     * @covers Alchemy\Phrasea\Border\Manager::getCheckers
     */
    public function testRegisterCheckers()
    {
        $this->assertEquals([], $this->object->getCheckers());

        $shaChecker = new Sha256(self::$DI['app']);
        $uuidChecker = new UUID(self::$DI['app']);
        $this->object->registerCheckers([$shaChecker, $uuidChecker]);

        $this->assertEquals([$shaChecker, $uuidChecker], $this->object->getCheckers());
    }

    /**
     * @covers Alchemy\Phrasea\Border\Manager::unregisterChecker
     */
    public function testUnregisterChecker()
    {
        $this->assertEquals([], $this->object->getCheckers());

        $shaChecker = new Sha256(self::$DI['app']);
        $uuidChecker = new UUID(self::$DI['app']);
        $filenameChecker = new Filename(self::$DI['app']);
        $this->object->registerCheckers([$shaChecker, $uuidChecker, $filenameChecker]);

        $this->assertEquals([$shaChecker, $uuidChecker, $filenameChecker], $this->object->getCheckers());

        $this->object->unregisterChecker($uuidChecker);
        $this->assertEquals([$shaChecker, $filenameChecker], $this->object->getCheckers());
    }
}

class ManagerTester extends Manager
{
    public function addMediaAttributesTester($file)
    {
        return parent::addMediaAttributes($file);
    }
}
