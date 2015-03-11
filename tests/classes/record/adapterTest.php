<?php

use Rhumsaa\Uuid\Uuid;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\Event;
use Doctrine\Common\Collections\ArrayCollection;

class record_adapterTest extends \PhraseanetAuthenticatedTestCase
{
    private static $thumbtitled = false;

    public function setUp()
    {
        parent::setUp();

        /**
         * Reset thumbtitle in order to have consistent tests (testGet_title)
         */
        if (!self::$thumbtitled) {
            foreach (self::$DI['record_1']->get_databox()->get_meta_structure() as $databox_field) {

                /* @var $databox_field \databox_field */
                $databox_field->set_thumbtitle(false)->save();
            }
            self::$thumbtitled = true;
        }
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
        $acl = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $acl->expects($this->any())
            ->method('has_right')
            ->with($this->equalTo('order'))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_access_to_record')
            ->with($this->isInstanceOf('\record_adapter'))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_right_on_base')
            ->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT), $this->equalTo('cancmd'))
            ->will($this->returnValue(true));
        $acl->expects($this->any())
            ->method('has_right_on_sbas')
            ->with($this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_INT), $this->equalTo('cancmd'))
            ->will($this->returnValue(true));

        $aclProvider = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProvider->expects($this->any())
            ->method('get')
            ->will($this->returnValue($acl));

        self::$DI['app']['acl'] = $aclProvider;

        self::$DI['app']['phraseanet.user-query'] = $this->getMockBuilder('\User_Query')->disableOriginalConstructor()->getMock();
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('get_results')->will($this->returnValue(new ArrayCollection([self::$DI['user_alt2']])));
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('on_base_ids')->will($this->returnSelf());
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('who_have_right')->will($this->returnSelf());
        self::$DI['app']['phraseanet.user-query']->expects($this->any())->method('execute')->will($this->returnSelf());

        self::$DI['app']['notification.deliverer'] = $this->getMockBuilder('Alchemy\Phrasea\Notification\Deliverer')
            ->disableOriginalConstructor()
            ->getMock();
        $triggered = false;
        self::$DI['app']['dispatcher']->addListener(PhraseaEvents::ORDER_CREATE, function (Event $event) use (&$triggered) {
            $triggered = true;
        });

        self::$DI['client']->request('POST', self::$DI['app']['url_generator']->generate('prod_order_new'), [
            'lst'      => self::$DI['record_1']->get_serialize_key(),
            'deadline' => '+10 minutes'
        ]);

        $this->assertTrue($triggered);
    }

    public function testGet_creation_date()
    {
        $date_obj = new DateTime();
        $this->assertTrue(self::$DI['record_1']->get_creation_date() instanceof DateTime);
        $this->assertTrue(
            self::$DI['record_1']->get_creation_date() <= $date_obj,
            sprintf('Asserting that %s is before %s', self::$DI['record_1']->get_creation_date()->format(DATE_ATOM), $date_obj->format(DATE_ATOM))
        );
    }

    public function testGet_uuid()
    {
        $this->assertTrue(Uuid::isValid(self::$DI['record_1']->get_uuid()));
    }

    public function testGet_modification_date()
    {
        $date_obj = new DateTime();
        $this->assertTrue((self::$DI['record_1']->get_creation_date() instanceof DateTime));
        $this->assertTrue(
            self::$DI['record_1']->get_creation_date() <= $date_obj,
            sprintf('Asserting that %s is before %s', self::$DI['record_1']->get_creation_date()->format(DATE_ATOM), $date_obj->format(DATE_ATOM))
        );
    }

    public function testGet_number()
    {
        self::$DI['record_1']->set_number(24);
        $this->assertEquals(24, self::$DI['record_1']->get_number());
        self::$DI['record_1']->set_number(42);
        $this->assertEquals(42, self::$DI['record_1']->get_number());
        self::$DI['record_1']->set_number(0);
        $this->assertEquals(0, self::$DI['record_1']->get_number());
        self::$DI['record_1']->set_number(null);
        $this->assertEquals(0, self::$DI['record_1']->get_number());
    }

    public function testSet_number()
    {
        $this->testGet_number();
    }

    public function testSet_type()
    {
        try {
            self::$DI['record_1']->set_type('jambon');
            $this->fail();
        } catch (Exception $e) {

        }
        $old_type = self::$DI['record_1']->get_type();
        self::$DI['record_1']->set_type('video');
        $this->assertEquals('video', self::$DI['record_1']->get_type());
        self::$DI['record_1']->set_type($old_type);
        $this->assertEquals($old_type, self::$DI['record_1']->get_type());
    }

    public function testIs_grouping()
    {
        $this->assertFalse(self::$DI['record_1']->is_grouping());
        $this->assertTrue(self::$DI['record_story_1']->is_grouping());
    }

    public function testGet_base_id()
    {
        $this->assertTrue(is_int(self::$DI['record_1']->get_base_id()));
        $this->assertEquals(self::$DI['collection']->get_base_id(), self::$DI['record_1']->get_base_id());
        $this->assertTrue(is_int(self::$DI['record_story_1']->get_base_id()));
        $this->assertEquals(self::$DI['collection']->get_base_id(), self::$DI['record_story_1']->get_base_id());
    }

    public function testGet_record_id()
    {
        $this->assertTrue(is_int(self::$DI['record_1']->get_record_id()));
        $this->assertTrue(is_int(self::$DI['record_story_1']->get_record_id()));
    }

    public function testGet_thumbnail()
    {
        $this->assertTrue((self::$DI['record_1']->get_thumbnail() instanceof media_subdef));
    }

    public function testGet_embedable_medias()
    {
        $embeddables = self::$DI['record_1']->get_embedable_medias();
        $this->assertTrue(is_array($embeddables));
        foreach ($embeddables as $subdef) {
            $this->assertInstanceOf('media_subdef', $subdef);
        }
    }

    public function testGet_type()
    {
        $this->assertTrue(in_array(self::$DI['record_1']->get_type(), ['video', 'audio', 'image', 'document', 'flash', 'unknown']));
    }

    public function testGet_formated_duration()
    {
        $this->assertEquals('', self::$DI['record_1']->get_formated_duration());
    }

    public function testGet_duration()
    {
        $this->assertEquals(false, self::$DI['record_1']->get_duration());
    }

    public function testGet_rollover_thumbnail()
    {
        $this->assertNull(self::$DI['record_1']->get_rollover_thumbnail());
    }

    public function testGet_sha256()
    {
        $this->assertNotNull(self::$DI['record_1']->get_sha256());
        $this->assertRegExp('/[a-zA-Z0-9]{32}/', self::$DI['record_1']->get_sha256());
        $this->assertNull(self::$DI['record_story_1']->get_sha256());
    }

    public function testGet_mime()
    {
        $this->assertRegExp('/image\/\w+/', self::$DI['record_1']->get_mime());
    }

    public function testGet_status()
    {
        $this->assertRegExp('/[01]{32}/', self::$DI['record_1']->get_status());
    }

    public function testGet_subdef()
    {
        $this->assertInstanceOf('media_subdef', self::$DI['record_1']->get_subdef('document'));
        $this->assertInstanceOf('media_subdef', self::$DI['record_1']->get_subdef('preview'));
        $this->assertInstanceOf('media_subdef', self::$DI['record_1']->get_subdef('thumbnail'));
    }

    public function testGet_subdefs()
    {
        $subdefs = self::$DI['record_1']->get_subdefs();
        $this->assertTrue(is_array($subdefs));
        foreach ($subdefs as $subdef) {
            $this->assertInstanceOf('media_subdef', $subdef);
        }
    }

    public function testGet_technical_infos()
    {
        $this->assertTrue(is_array(self::$DI['record_1']->get_technical_infos()));
    }

    public function testGet_caption()
    {
        $this->assertTrue((self::$DI['record_1']->get_caption() instanceof caption_record));
    }

    public function testGet_original_name()
    {
        $this->assertEquals('test001.jpg', self::$DI['record_1']->get_original_name());
    }

    public function testGet_title()
    {
        $this->markTestSkipped('Unable to test title');

        $this->assertEquals('test001.jpg', self::$DI['record_1']->get_title());
    }

    public function testGet_preview()
    {
        $this->assertTrue((self::$DI['record_1']->get_preview() instanceof media_subdef));
    }

    public function testHas_preview()
    {
        $this->assertTrue(self::$DI['record_1']->has_preview());
    }

    public function testGet_serialize_key()
    {
        $this->assertTrue(self::$DI['record_1']->get_serialize_key() == self::$DI['record_1']->get_sbas_id() . '_' . self::$DI['record_1']->get_record_id());
    }

    public function testGet_sbas_id()
    {
        $this->assertTrue(is_int(self::$DI['record_1']->get_sbas_id()));
    }

    public function testSet_metadatas()
    {

        $meta_structure_el = self::$DI['collection']->get_databox()->get_meta_structure()->get_elements();

        $current_caption = self::$DI['record_1']->get_caption();

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

        self::$DI['app']['phraseanet.SE'] = $this->createSearchEngineMock();
        self::$DI['record_1']->set_metadatas($metadatas, true);

        $caption = self::$DI['record_1']->get_caption();

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
        self::$DI['record_1']->rebuild_subdefs();
        $sql = 'SELECT record_id
              FROM record
              WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
              AND record_id = :record_id';
        $stmt = self::$DI['record_1']->get_databox()->get_connection()->prepare($sql);

        $stmt->execute([':record_id' => self::$DI['record_1']->get_record_id()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != self::$DI['record_1']->get_record_id())
            $this->fail();
    }

    public function testWrite_metas()
    {
        self::$DI['record_1']->write_metas();
        $sql = 'SELECT record_id, coll_id, jeton
            FROM record WHERE (jeton & ' . JETON_WRITE_META . ' > 0)
            AND record_id = :record_id';
        $stmt = self::$DI['record_1']->get_databox()->get_connection()->prepare($sql);

        $stmt->execute([':record_id' => self::$DI['record_1']->get_record_id()]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != self::$DI['record_1']->get_record_id())
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

        self::$DI['record_1']->set_binary_status($status);

        $this->assertEquals($status, self::$DI['record_1']->get_status());
    }

    public function testGet_record_by_sha()
    {
        $tmp_records = record_adapter::get_record_by_sha(self::$DI['app'], self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_sha256());
        $this->assertTrue(is_array($tmp_records));

        foreach ($tmp_records as $tmp_record) {
            $this->assertInstanceOf('record_adapter', $tmp_record);
            $this->assertEquals(self::$DI['record_1']->get_sha256(), $tmp_record->get_sha256());
        }

        $tmp_records = record_adapter::get_record_by_sha(self::$DI['app'], self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_sha256(), self::$DI['record_1']->get_record_id());
        $this->assertTrue(is_array($tmp_records));
        $this->assertTrue(count($tmp_records) === 1);

        foreach ($tmp_records as $tmp_record) {
            $this->assertInstanceOf('record_adapter', $tmp_record);
            $this->assertEquals(self::$DI['record_1']->get_sha256(), $tmp_record->get_sha256());
            $this->assertEquals(self::$DI['record_1']->get_record_id(), $tmp_record->get_record_id());
        }
    }

    public function testGet_hd_file()
    {
        $this->assertInstanceOf('\SplFileInfo', self::$DI['record_1']->get_hd_file());
    }


    public function testGet_container_baskets()
    {
        $basket = self::$DI['app']['orm.em']->find('Phraseanet:Basket', 1);
        $found = $sselcont_id = false;

        $sbas_id = self::$DI['record_1']->get_sbas_id();
        $record_id = self::$DI['record_1']->get_record_id();

        foreach (self::$DI['record_1']->get_container_baskets(self::$DI['app']['orm.em'], self::$DI['user']) as $c_basket) {
            if ($c_basket->getId() == $basket->getId()) {
                $found = true;
                foreach ($c_basket->getElements() as $b_el) {
                    if ($b_el->getRecord(self::$DI['app'])->get_record_id() == $record_id && $b_el->getRecord(self::$DI['app'])->get_sbas_id() == $sbas_id)
                        $sselcont_id = $b_el->getId();
                }
            }
        }

        if ( ! $found)
            $this->fail();
    }

    public function testSetStatus()
    {
        $record = new \record_adapter(self::$DI['app'], self::$DI['record_1']->get_sbas_id(), self::$DI['record_1']->get_record_id());
        $record->set_binary_status('1001001001010101');
        $this->assertSame('00000000000000001001001001010101', $record->get_status());
    }
}
