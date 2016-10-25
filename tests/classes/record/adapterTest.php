<?php

use Alchemy\Phrasea\Core\PhraseaEvents;
use Doctrine\Common\Collections\ArrayCollection;
use Ramsey\Uuid\Uuid;
use Symfony\Component\EventDispatcher\Event;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 */
class record_adapterTest extends \PhraseanetAuthenticatedTestCase
{
    private static $thumbtitled = false;
    private $originalName;

    public function setUp()
    {
        parent::setUp();

        // Reset thumbtitle in order to have consistent tests (testGet_title)
        if (!self::$thumbtitled) {
            foreach ($this->getRecord1()->getDatabox()->get_meta_structure() as $databox_field) {
                $databox_field->set_thumbtitle(false)->save();
            }
            self::$thumbtitled = true;
        }
    }

    public function tearDown()
    {
        if ($this->originalName && $this->originalName !== $this->getRecord1()->get_original_name()) {
            $this->getRecord1()->set_original_name($this->originalName);
        }

        parent::tearDown();
    }


    public static function tearDownAfterClass()
    {
        self::$thumbtitled = null;
        parent::tearDownAfterClass();
    }

    /**
     *  Check whether a record is delete from order_elements when
     *  record::delete is call
     * @covers \record_adapter
     */
    public function testSetExport()
    {
        $acl = $this->stubACL();
        $acl->expects($this->any())
            ->method('has_right')
            ->with($this->equalTo(\ACL::CANCMD))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_access_to_record')
            ->with($this->isInstanceOf('\record_adapter'))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_right_on_base')
            ->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT), $this->equalTo(\ACL::CANCMD))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_right_on_sbas')
            ->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT), $this->equalTo(\ACl::CANCMD))
            ->will($this->returnValue(true));

        $app = $this->getApplication();
        $app['phraseanet.user-query'] = $this->getMockBuilder('\User_Query')->disableOriginalConstructor()->getMock();
        $app['phraseanet.user-query']->expects($this->any())->method('get_results')->will($this->returnValue(new ArrayCollection([self::$DI['user_alt2']])));
        $app['phraseanet.user-query']->expects($this->any())->method('on_base_ids')->will($this->returnSelf());
        $app['phraseanet.user-query']->expects($this->any())->method('who_have_right')->will($this->returnSelf());
        $app['phraseanet.user-query']->expects($this->any())->method('execute')->will($this->returnSelf());

        $app['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();
        $triggered = false;
        $app['dispatcher']->addListener(PhraseaEvents::ORDER_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        $this->getClient()->request(
            'POST', $app['url_generator']->generate('prod_order_new'), [
            'lst'      => $this->getRecord1()->getId(),
            'deadline' => '+10 minutes'
        ]);

        $this->assertTrue($triggered);
    }

    public function testGetCreated()
    {
        $date_obj = new DateTime();
        $record_1 = $this->getRecord1();
        $this->assertTrue($record_1->getCreated() instanceof DateTime);
        $this->assertTrue(
            $record_1->getCreated() <= $date_obj,
            sprintf('Asserting that %s is before %s', $record_1->getCreated()->format(DATE_ATOM), $date_obj->format(DATE_ATOM))
        );
    }

    public function testGet_uuid()
    {
        $this->assertTrue(Uuid::isValid($this->getRecord1()->getUuid()));
    }

    public function testGetUpdated()
    {
        $date_obj = new DateTime();
        $record_1 = $this->getRecord1();
        $this->assertTrue(($record_1->getUpdated() instanceof DateTime));
        $this->assertTrue(
            $record_1->getCreated() <= $date_obj,
            sprintf('Asserting that %s is before %s', $record_1->getUpdated()->format(DATE_ATOM), $date_obj->format(DATE_ATOM))
        );
    }

    public function testNumber()
    {
        $record_1 = $this->getRecord1();
        $record_1->setNumber(24);
        $this->assertEquals(24, $record_1->getNumber());
        $record_1->setNumber(42);
        $this->assertEquals(42, $record_1->getNumber());
        $record_1->setNumber(0);
        $this->assertEquals(0, $record_1->getNumber());
        $record_1->setNumber(null);
        $this->assertEquals(0, $record_1->getNumber());
    }

    public function testSet_type()
    {
        $record_1 = $this->getRecord1();
        try {
            $record_1->setType('jambon');
            $this->fail();
        } catch (Exception $e) {

        }
        $old_type = $record_1->getType();
        $record_1->setType('video');
        $this->assertEquals('video', $record_1->getType());
        $record_1->setType($old_type);
        $this->assertEquals($old_type, $record_1->getType());
    }

    public function testIs_grouping()
    {
        $this->assertFalse($this->getRecord1()->isStory());
        $this->assertTrue($this->getRecordStory1()->isStory());
    }

    public function testGet_base_id()
    {
        $record_1 = $this->getRecord1();
        $this->assertTrue(is_int($record_1->getBaseId()));
        $this->assertEquals($this->getCollection()->get_base_id(), $record_1->getBaseId());
        $record_story_1 = $this->getRecordStory1();
        $this->assertTrue(is_int($record_story_1->getBaseId()));
        $this->assertEquals($this->getCollection()->get_base_id(), $record_story_1->getBaseId());
    }

    public function testGet_record_id()
    {
        $this->assertTrue(is_int($this->getRecord1()->getRecordId()));
        $this->assertTrue(is_int($this->getRecordStory1()->getRecordId()));
    }

    public function testGet_thumbnail()
    {
        $this->assertTrue(($this->getRecord1()->get_thumbnail() instanceof media_subdef));
    }

    public function testGet_embedable_medias()
    {
        $embeddables = $this->getRecord1()->get_embedable_medias();
        $this->assertTrue(is_array($embeddables));
        foreach ($embeddables as $subdef) {
            $this->assertInstanceOf('media_subdef', $subdef);
        }
    }

    public function testGet_type()
    {
        $this->assertTrue(in_array($this->getRecord1()->getType(), ['video', 'audio', 'image', 'document', 'flash', 'unknown']));
    }

    public function testGet_formatted_duration()
    {
        $this->assertEquals('', $this->getRecord1()->get_formated_duration());
    }

    public function testGet_duration()
    {
        $this->assertEquals(false, $this->getRecord1()->get_duration());
    }

    public function testGet_rollover_thumbnail()
    {
        $this->assertNull($this->getRecord1()->get_rollover_thumbnail());
    }

    public function testGet_sha256()
    {
        $record_1 = $this->getRecord1();
        $this->assertNotNull($record_1->getSha256());
        $this->assertRegExp('/[a-zA-Z0-9]{32}/', $record_1->getSha256());
        $this->assertNull($this->getRecordStory1()->getSha256());
    }

    public function testGet_mime()
    {
        $this->assertRegExp('/image\/\w+/', $this->getRecord1()->getMimeType());
    }

    public function testSetMimeType()
    {
        $record_1 = $this->getRecord1();

        $oldMime = $record_1->getMimeType();
        $record_1->setMimeType('foo/bar');
        $this->assertEquals('foo/bar', $record_1->getMimeType());

        $record_1->setMimeType($oldMime);
        $this->assertEquals($oldMime, $record_1->getMimeType());
    }

    public function testGet_status()
    {
        $this->assertRegExp('/[01]{32}/', $this->getRecord1()->getStatus());
    }

    public function testGet_subdef()
    {
        $record_1 = $this->getRecord1();
        $this->assertInstanceOf('media_subdef', $record_1->get_subdef('document'));
        $this->assertInstanceOf('media_subdef', $record_1->get_subdef('preview'));
        $this->assertInstanceOf('media_subdef', $record_1->get_subdef('thumbnail'));
    }

    public function testGet_subdefs()
    {
        $subdefs = $this->getRecord1()->get_subdefs();
        $this->assertTrue(is_array($subdefs));
        foreach ($subdefs as $subdef) {
            $this->assertInstanceOf('media_subdef', $subdef);
        }
    }

    public function testGet_technical_infos()
    {
        $this->assertInternalType('array', $this->getRecord1()->get_technical_infos()->getValues());
    }

    public function testGet_caption()
    {
        $this->assertTrue(($this->getRecord1()->get_caption() instanceof caption_record));
    }

    public function testGet_original_name()
    {
        $this->assertEquals('test001.jpg', $this->getRecord1()->get_original_name());
    }

    public function testSetOriginalName()
    {
        $record_1 = $this->getRecord1();
        $this->originalName = $record_1->get_original_name();

        $record_1->set_original_name('test001-renamed.jpg');

        $this->assertEquals('test001-renamed.jpg', $record_1->get_original_name());

        // Retrieve filename whatever template locale selected
        $values = null;
        foreach ($record_1->get_caption()->get_fields(['FileName', 'NomDeFichier'], true) as $value) {
            $values = $value->get_values();
        }

        $this->assertCount(1, $values);
        foreach ($values as $value) {
            $this->assertEquals('test001-renamed.jpg', $value->getValue());
        }
    }

    public function testGet_title()
    {
        // Test was marked skipped for unknown reason
        $this->assertEquals('test001.jpg', $this->getRecord1()->get_title());
    }

    public function testGet_preview()
    {
        $this->assertTrue(($this->getRecord1()->get_preview() instanceof media_subdef));
    }

    public function testHas_preview()
    {
        $this->assertTrue($this->getRecord1()->has_preview());
    }

    public function testGet_serialize_key()
    {
        $record_1 = $this->getRecord1();
        $this->assertTrue($record_1->getId() == $record_1->getDataboxId() . '_' . $record_1->getRecordId());
    }

    public function testGet_sbas_id()
    {
        $this->assertTrue(is_int($this->getRecord1()->getDataboxId()));
    }

    public function testSet_metadatas()
    {

        $meta_structure_el = $this->getCollection()->get_databox()->get_meta_structure()->get_elements();

        $record_1 = $this->getRecord1();
        $current_caption = $record_1->get_caption();

        $metadatas = [];

        foreach ($meta_structure_el as $meta_el) {
            $current_fields = $current_caption->get_fields([$meta_el->get_name()]);

            $field = null;

            if (count($current_fields) > 0) {
                $field = array_pop($current_fields);
            }

            if ($meta_el->is_multi()) {
                if ($field) {
                    foreach ($field->get_values() as $value) {
                        $metadatas[] = [
                            'meta_struct_id' => $meta_el->get_id()
                            , 'meta_id'        => $value->getId()
                            , 'value'          => ''
                        ];
                    }
                }

                $metadatas[] = [
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'un'
                ];
                $metadatas[] = [
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'jeu'
                ];
                $metadatas[] = [
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'de'
                ];
                $metadatas[] = [
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'test'
                ];
            } else {
                $meta_id = null;

                if ($field) {
                    $values = $field->get_values();
                    $meta_id = array_pop($values)->getId();
                }

                $metadatas[] = [
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => $meta_id
                    , 'value'          => 'un premier jeu de test'
                ];

                $metadatas[] = [
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => $meta_id
                    , 'value'          => 'un second jeu de test'
                ];
            }
        }

        $app = $this->getApplication();
        $app['phraseanet.SE'] = $this->createSearchEngineMock();
        $record_1->set_metadatas($metadatas, true);

        $caption = $record_1->get_caption();

        foreach ($meta_structure_el as $meta_el) {
            $current_fields = $caption->get_fields([$meta_el->get_name()], true);

            $this->assertEquals(1, count($current_fields));
            $field = $current_fields[0];

            $separator = $meta_el->get_separator();

            if (strlen($separator) > 0) {
                $separator = $separator[0];
            } else {
                $separator = '';
            }

            $multi_imploded = implode(' ' . $separator . ' ', ['un', 'jeu', 'de', 'test']);

            if ($meta_el->is_multi()) {
                $initial_values = [];
                foreach ($field->get_values() as $value) {
                    $initial_values[] = $value->getValue();
                }

                $this->assertEquals($multi_imploded, implode(' ' . $meta_el->get_separator() . ' ', $initial_values));
                $this->assertEquals($multi_imploded, $field->get_serialized_values());
            } else {
                $this->assertEquals('un second jeu de test', $field->get_serialized_values());
            }
        }
    }

    public function testRebuild_subdefs()
    {
        $record_1 = $this->getRecord1();
        $record_1->rebuild_subdefs();
        $sql = 'SELECT record_id
              FROM record
              WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
              AND record_id = :record_id';
        $stmt = $record_1->getDatabox()->get_connection()->prepare($sql);

        $stmt->execute([':record_id' => $record_1->getRecordId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != $record_1->getRecordId())
            $this->fail();
    }

    public function testWrite_metas()
    {
        $record_1 = $this->getRecord1();
        $record_1->write_metas();
        $sql = 'SELECT record_id, coll_id, jeton
            FROM record WHERE (jeton & ' . JETON_WRITE_META . ' > 0)
            AND record_id = :record_id';
        $stmt = $record_1->getDatabox()->get_connection()->prepare($sql);

        $stmt->execute([':record_id' => $record_1->getRecordId()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != $record_1->getRecordId())
            $this->fail();
    }

    /**
     * @todo Implement testSet_binary_status().
     */
    public function testSet_binary_status()
    {
        $status = '';

        while (strlen($status) < 32) {
            $status .= '1';
        }

        $record_1 = $this->getRecord1();
        $record_1->setStatus($status);

        $this->assertEquals($status, $record_1->getStatus());
    }

    public function testGet_record_by_sha()
    {
        $record_1 = $this->getRecord1();
        $tmp_records = record_adapter::get_record_by_sha($record_1->getDatabox(), $record_1->getSha256());
        $this->assertTrue(is_array($tmp_records));

        foreach ($tmp_records as $tmp_record) {
            $this->assertInstanceOf('record_adapter', $tmp_record);
            $this->assertEquals($record_1->getSha256(), $tmp_record->getSha256());
        }

        $tmp_records = record_adapter::get_record_by_sha(
            $record_1->getDatabox(),
            $record_1->getSha256(),
            $record_1->getRecordId()
        );
        $this->assertTrue(is_array($tmp_records));
        $this->assertTrue(count($tmp_records) === 1);

        foreach ($tmp_records as $tmp_record) {
            $this->assertInstanceOf('record_adapter', $tmp_record);
            $this->assertEquals($record_1->getSha256(), $tmp_record->getSha256());
            $this->assertEquals($record_1->getRecordId(), $tmp_record->getRecordId());
        }
    }

    public function testGet_hd_file()
    {
        $this->assertInstanceOf('\SplFileInfo', $this->getRecord1()->get_hd_file());
    }


    public function testGet_container_baskets()
    {
        $app = $this->getApplication();
        $basket = $app['orm.em']->find('Phraseanet:Basket', 1);
        $found = $sselcont_id = false;

        $record_1 = $this->getRecord1();
        $sbas_id = $record_1->getDataboxId();
        $record_id = $record_1->getRecordId();

        foreach ($record_1->get_container_baskets($app['orm.em'], self::$DI['user']) as $c_basket) {
            if ($c_basket->getId() == $basket->getId()) {
                $found = true;
                foreach ($c_basket->getElements() as $b_el) {
                    if ($b_el->getRecord($app)->get_record_id() == $record_id && $b_el->getRecord($app)->get_sbas_id() == $sbas_id)
                        $sselcont_id = $b_el->getId();
                }
            }
        }

        if ( ! $found)
            $this->fail();
    }

    public function testSetStatus()
    {
        $record_1 = $this->getRecord1();
        $record = new \record_adapter($this->getApplication(), $record_1->getDataboxId(), $record_1->getRecordId());
        $record->setStatus('1001001001010101');
        $this->assertSame('00000000000000001001001001010101', $record->getStatus());
    }
}
