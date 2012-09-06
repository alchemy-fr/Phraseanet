<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class task_period_archiveTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var archiveTester
     */
    protected static $object;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        $app = new \Alchemy\Phrasea\Application();
        $task = \task_period_archive::create($app, 'task_period_archive');

        $logger = new \Monolog\Logger('test');
        $logger->pushHandler(new \Monolog\Handler\NullHandler());

        self::$object = new archiveTester($task->getID(), $app, $logger);
    }

    public static function tearDownAfterClass()
    {
        self::$object->delete();
        parent::tearDownAfterClass();
    }

    /**
     * @covers task_period_archive::getName
     * @todo Implement testGetName().
     */
    public function testGetName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::graphic2xml
     * @todo Implement testGraphic2xml().
     */
    public function testGraphic2xml()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::xml2graphic
     * @todo Implement testXml2graphic().
     */
    public function testXml2graphic()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::printInterfaceJS
     * @todo Implement testPrintInterfaceJS().
     */
    public function testPrintInterfaceJS()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::getInterfaceHTML
     * @todo Implement testGetInterfaceHTML().
     */
    public function testGetInterfaceHTML()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::help
     * @todo Implement testHelp().
     */
    public function testHelp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::archiveHotFolder
     * @todo Implement testArchiveHotFolder().
     */
    public function testArchiveHotFolder()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::isIgnoredFile
     * @todo Implement testIsIgnoredFile().
     */
    public function testIsIgnoredFile()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::checkMatch
     * @todo Implement testCheckMatch().
     */
    public function testCheckMatch()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::listFilesPhase1
     * @todo Implement testListFilesPhase1().
     */
    public function testListFilesPhase1()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::listFilesPhase2
     * @todo Implement testListFilesPhase2().
     */
    public function testListFilesPhase2()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::makePairs
     * @todo Implement testMakePairs().
     */
    public function testMakePairs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::removeBadGroups
     * @todo Implement testRemoveBadGroups().
     */
    public function testRemoveBadGroups()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::archive
     * @todo Implement testArchive().
     */
    public function testArchive()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::bubbleResults
     * @todo Implement testBubbleResults().
     */
    public function testBubbleResults()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::moveFiles
     * @todo Implement testMoveFiles().
     */
    public function testMoveFiles()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::setBranchHot
     * @todo Implement testSetBranchHot().
     */
    public function testSetBranchHot()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::archiveGrp
     * @todo Implement testArchiveGrp().
     */
    public function testArchiveGrp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function getXml()
    {
        $meta_struct = self::$collection->get_databox()->get_meta_structure();

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <record record_id="2">
                    <description>' . "\n";

        $tofetch = array();

        foreach ($meta_struct as $databox_field) {
            if ($databox_field->is_multi()) {

                $tofetch[$databox_field->get_name()] = array();

                $values = array('a-value-' . $databox_field->get_id(), 'b-value-' . $databox_field->get_id());

                $tofetch[$databox_field->get_name()] = array_merge($tofetch[$databox_field->get_name()], $values);

                $xml .= '<' . $databox_field->get_name() . '>';
                $xml .= implode(' ; ', $values);
                $xml .= '</' . $databox_field->get_name() . '>' . "\n";

                $values = array('c-value-' . $databox_field->get_id(), 'd-value-' . $databox_field->get_id());

                $tofetch[$databox_field->get_name()] = array_merge($tofetch[$databox_field->get_name()], $values);

                foreach ($values as $value) {
                    $xml .= '<' . $databox_field->get_name() . '>';
                    $xml .= $value;
                    $xml .= '</' . $databox_field->get_name() . '>' . "\n";
                }
            } else {

                $value = 'c-value-' . $databox_field->get_id();

                $xml .= '<' . $databox_field->get_name() . '>';
                $xml .= $value;
                $xml .= '</' . $databox_field->get_name() . '>' . "\n";

                $tofetch[$databox_field->get_name()] = array($value);
            }
        }

        $xml .= '</description>
                </record>
                ';

        return array($xml, $tofetch, $meta_struct);
    }

    /**
     * @covers task_period_archive::createStory
     * @todo Implement testCreateStory().
     */
    public function testCreateStory()
    {
        list($xml, $tofetch, $meta_struct) = $this->getXml();

        $tmp = sys_get_temp_dir() . '/tmp' . time() . mt_rand(1000, 9999) . '.xml';
        $tmpFile = sys_get_temp_dir() . '/tmp' . time() . mt_rand(1000, 9999) . '.jpg';
        copy(__DIR__ . '/../../testfiles/cestlafete.jpg', $tmpFile);

        file_put_contents($tmp, $xml);

        $story = self::$object->createStory(self::$collection, $tmpFile, $tmp);

        unlink($tmpFile);

        $this->assertTrue($story->is_grouping());
    }

    /**
     * @covers task_period_archive::createRecord
     * @todo Implement testCreateRecord().
     */
    public function testCreateRecord()
    {
        list($xml, $tofetch, $meta_struct) = $this->getXml();

        $tmp = sys_get_temp_dir() . '/tmp' . time() . mt_rand(1000, 9999) . '.xml';
        $tmpFile = sys_get_temp_dir() . '/tmp' . time() . mt_rand(1000, 9999) . '.jpg';
        copy(__DIR__ . '/../../testfiles/cestlafete.jpg', $tmpFile);

        file_put_contents($tmp, $xml);

        $story = self::$object->createRecord(self::$collection, $tmpFile, $tmp, null, \Alchemy\Phrasea\Border\Manager::FORCE_RECORD);

        unlink($tmpFile);

        $this->assertFalse($story->is_grouping());
    }

    /**
     * @covers task_period_archive::archiveFilesToGrp
     * @todo Implement testArchiveFilesToGrp().
     */
    public function testArchiveFilesToGrp()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::archiveFile
     * @todo Implement testArchiveFile().
     */
    public function testArchiveFile()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::archiveFileAndCaption
     * @todo Implement testArchiveFileAndCaption().
     */
    public function testArchiveFileAndCaption()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::setAllChildren
     * @todo Implement testSetAllChildren().
     */
    public function testSetAllChildren()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::getGrpSettings
     * @todo Implement testGetGrpSettings().
     */
    public function testGetGrpSettings()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @covers task_period_archive::getLazaretSession
     */
    public function testGetLazaretSession()
    {
        $session = self::$object->getLazaretSessionTester();
        $this->assertInstanceOf('\\Entities\\LazaretSession', $session);

        $session_id = self::$object->getLazaretSessionTester()->getId();

        $session = self::$object->getLazaretSessionTester();

        $this->assertEquals($session_id, $session->getId());
    }

    /**
     * @covers task_period_archive::getIndexByFieldName
     */
    public function testGetIndexByFieldName()
    {
        $meta_struct = self::$collection->get_databox()->get_meta_structure();
        $media = self::$core['mediavorus']->guess(new \SplFileInfo(__DIR__ . '/../../testfiles/test001.CR2'));

        $bagByName = self::$object->getIndexByFieldNameTester($meta_struct, $media->getEntity()->getMetadatas());

        $this->assertInstanceOf('\\PHPExiftool\\Driver\\Metadata\\MetadataBag', $bagByName);

        if (count($bagByName) == 0) {
            $this->markTestSkipped('No enough data to tests');
        }

        foreach ($bagByName as $fieldname => $value) {
            $this->assertNotNull($meta_struct->get_element_by_name($fieldname));
            $this->assertInstanceOf('\\databox_field', $meta_struct->get_element_by_name($fieldname));
            $this->assertInstanceof('\\PHPExiftool\\Driver\\Metadata\\Metadata', $value);
        }
    }

    /**
     * @covers task_period_archive::bagToArray
     */
    public function testBagToArray()
    {
        $meta_struct = self::$collection->get_databox()->get_meta_structure();

        $bag = new PHPExiftool\Driver\Metadata\MetadataBag();

        $first_multi = $first_mono = false;
        $toFetch = array();

        foreach ($meta_struct as $databox_field) {
            $tagname = $databox_field->get_tag()->getTagname();
            if ( ! $tagname) {
                continue;
            }

            if ($databox_field->is_multi()) {
                if ($first_multi) {
                    $bag->set($tagname, new PHPExiftool\Driver\Metadata\Metadata(
                            $databox_field->get_tag(),
                            new \PHPExiftool\Driver\Value\Mono('c-multi-' . $databox_field->get_id())
                    ));
                    $toFetch[$tagname][] = 'c-multi-' . $databox_field->get_id();
                } else {
                    $bag->set($tagname, new PHPExiftool\Driver\Metadata\Metadata(
                            $databox_field->get_tag(),
                            new \PHPExiftool\Driver\Value\Multi(array('a-multi-' . $databox_field->get_id(), 'b-multi-' . $databox_field->get_id()))
                    ));
                    $toFetch[$tagname][] = 'a-multi-' . $databox_field->get_id();
                    $toFetch[$tagname][] = 'b-multi-' . $databox_field->get_id();
                }
                $first_multi = false;
            } else {
                if ($first_mono) {
                    $bag->set($tagname, new PHPExiftool\Driver\Metadata\Metadata(
                            $databox_field->get_tag(),
                            new \PHPExiftool\Driver\Value\Multi(array('a-mono-' . $databox_field->get_id(), 'b-mono-' . $databox_field->get_id()))
                    ));
                    $toFetch[$tagname] = array('b-mono-' . $databox_field->get_id());
                } else {
                    $bag->set($tagname, new PHPExiftool\Driver\Metadata\Metadata(
                            $databox_field->get_tag(),
                            new \PHPExiftool\Driver\Value\Mono('c-mono-' . $databox_field->get_id())
                    ));
                    $toFetch[$tagname] = array('c-mono-' . $databox_field->get_id());
                }
                $first_mono = false;
            }
        }

        $array = self::$object->bagToArrayTester($meta_struct, $bag);


        $written = $id_done = array();
        foreach ($array as $meta) {
            $databox_field = $meta_struct->get_element($meta['meta_struct_id']);
            $tagname = $databox_field->get_tag()->getTagname();

            if ( ! $databox_field->is_multi() && isset($id_done[$databox_field->get_id()])) {
                $this->fail('No duplicate for mono values');
            }

            $id_done[$databox_field->get_id()] = true;

            $written[$tagname][] = $meta['value'];

            $written[$tagname] = array_unique($written[$tagname]);
        }

        foreach ($written as $tagname => $values) {
            $this->assertEquals($values, $toFetch[$tagname]);
        }
    }

    /**
     * @covers task_period_archive::mergeForDatabox
     * @todo Implement testMergeForDatabox().
     */
    public function testMergeForDatabox()
    {
        $meta_struct = self::$collection->get_databox()->get_meta_structure();

        $bag1 = new PHPExiftool\Driver\Metadata\MetadataBag();
        $bag2 = new PHPExiftool\Driver\Metadata\MetadataBag();

        $tofetch = array();

        foreach ($meta_struct as $databox_field) {
            $tofetch[$databox_field->get_name()] = array();
            if ($databox_field->is_multi()) {
                $values = array('a-multi-' . $databox_field->get_id(), 'b-multi-' . $databox_field->get_id());
                $bag1->set(
                    $databox_field->get_name(), new PHPExiftool\Driver\Metadata\Metadata(
                        $databox_field->get_tag(),
                        new PHPExiftool\Driver\Value\Multi($values)
                    )
                );

                $tofetch[$databox_field->get_name()] = array_merge($tofetch[$databox_field->get_name()], $values);

                $values = array('a-multi-' . $databox_field->get_id(), 'd-multi-' . $databox_field->get_id());

                $bag2->set(
                    $databox_field->get_name(), new PHPExiftool\Driver\Metadata\Metadata(
                        $databox_field->get_tag(),
                        new PHPExiftool\Driver\Value\Multi($values)
                    )
                );

                $tofetch[$databox_field->get_name()] = array_values(array_unique(array_merge($tofetch[$databox_field->get_name()], $values)));
            } else {
                $value = 'a-mono-' . $databox_field->get_id();
                $bag1->set(
                    $databox_field->get_name(), new PHPExiftool\Driver\Metadata\Metadata(
                        $databox_field->get_tag(),
                        new PHPExiftool\Driver\Value\Mono($value)
                    )
                );

                $value = 'b-mono-' . $databox_field->get_id();
                $bag2->set(
                    $databox_field->get_name(), new PHPExiftool\Driver\Metadata\Metadata(
                        $databox_field->get_tag(),
                        new PHPExiftool\Driver\Value\Mono($value)
                    )
                );

                $tofetch[$databox_field->get_name()] = array($value);
            }
        }

        $bag = self::$object->mergeForDataboxTester($meta_struct, $bag1, $bag2);

        $found = array();

        foreach ($bag as $fieldname => $metadata) {
            $found[$fieldname] = $metadata->getValue()->asArray();
        }

        foreach ($tofetch as $fieldname => $tofound) {
            $this->assertEquals($tofound, $found[$fieldname]);
        }
    }

    /**
     * @covers task_period_archive::readXMLForDatabox
     * @expectedException \InvalidArgumentException
     */
    public function testReadXMLForDataboxFail()
    {
        $meta_struct = self::$collection->get_databox()->get_meta_structure();
        self::$object->readXMLForDataboxTester($meta_struct, 'non existant file');
    }

    /**
     * @covers task_period_archive::readXMLForDatabox
     * @expectedException \InvalidArgumentException
     */
    public function testReadXMLForDataboxWrongXML()
    {
        $meta_struct = self::$collection->get_databox()->get_meta_structure();
        self::$object->readXMLForDataboxTester($meta_struct, __FILE__);
    }

    /**
     * @covers task_period_archive::readXMLForDatabox
     */
    public function testReadXMLForDatabox()
    {
        list($xml, $tofetch, $meta_struct) = $this->getXml();

        $tmp = sys_get_temp_dir() . '/tmp' . time() . mt_rand(1000, 9999) . '.xml';
        file_put_contents($tmp, $xml);

        $bag = self::$object->readXMLForDataboxTester($meta_struct, $tmp);

        unlink($tmp);

        $found = array();

        foreach ($bag as $fieldname => $metadata) {
            $found[$fieldname] = $metadata->getValue()->asArray();
        }

        foreach ($tofetch as $fieldname => $values) {
            $this->assertEquals($values, $found[$fieldname]);
        }
    }

    /**
     * @covers task_period_archive::parseStatusBit
     */
    public function testParseStatusBit()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <record record_id="2">
                    <status>10000</status>
                    <description/>
                    <doc/>
                </record>';
        $sxcaption = simplexml_load_string($xml);
        $this->assertEquals('10000', self::$object->parseStatusBitTester($sxcaption));


        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <record record_id="2">
                    <status/>
                    <description/>
                    <doc/>
                </record>';
        $sxcaption = simplexml_load_string($xml);

        $this->assertNull(self::$object->parseStatusBitTester($sxcaption));
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <record record_id="2">
                </record>';
        $sxcaption = simplexml_load_string($xml);
        $this->assertNull(self::$object->parseStatusBitTester($sxcaption));
    }
}

class archiveTester extends task_period_archive
{

    public function getLazaretSessionTester()
    {
        return parent::getLazaretSession();
    }

    public function getIndexByFieldNameTester($meta_struct, $bag)
    {
        return parent::getIndexByFieldName($meta_struct, $bag);
    }

    public function bagToArrayTester($meta_struct, $bag)
    {
        return parent::bagToArray($meta_struct, $bag);
    }

    public function mergeForDataboxTester($meta_struct, $bag1, $bag2)
    {
        return parent::mergeForDatabox($meta_struct, $bag1, $bag2);
    }

    public function readXMLForDataboxTester($meta_struct, $pathfile)
    {
        return parent::readXMLForDatabox($meta_struct, $pathfile);
    }

    public function parseStatusBitTester($sxcaption)
    {
        return parent::parseStatusBit($sxcaption);
    }
}
