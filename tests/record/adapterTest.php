<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

use Alchemy\Phrasea\Controller\RecordsRequest;
use Doctrine\Common\Collections\ArrayCollection;

class record_adapterTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    /**
     * @var record_adapter
     */
    protected static $grouping;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        /**
         * Reset thumbtitle in order to have consistent tests (testGet_title)
         */
        foreach (static::$records['record_1']->get_databox()->get_meta_structure() as $databox_field) {

            /* @var $databox_field \databox_field */
            $databox_field->set_thumbtitle(false)->save();
        }
    }

    /**
     *  Check whether a record is delete from order_elements when
     *  record::delete is call
     * @covers \record_adapter
     */
    public function testSetExport()
    {
        $basket = new \Entities\Basket();

        $basket->setName('hello');
        $basket->setOwner(self::$user);
        $basket->setDescription('hello');

        $em = self::$core->getEntityManager();

        $basketElement = new \Entities\BasketElement();

        $basketElement->setRecord(static::$records['record_1']);
        $basketElement->setBasket($basket);

        $em->persist($basketElement);

        $basket->addBasketElement($basketElement);

        $em->persist($basket);
        $em->flush();

        $receveid = array(static::$records['record_1']->get_serialize_key() => static::$records['record_1']);

        return \set_order::create(
                \appbox::get_instance(self::$core), new RecordsRequest($receveid, new ArrayCollection($receveid), $basket), self::$user_alt2, 'I need this photos', new \DateTime('+10 minutes')
        );
    }

    public function testGet_creation_date()
    {
        $date_obj = new DateTime();
        $this->assertTrue((static::$records['record_1']->get_creation_date() instanceof DateTime));
        $this->assertTrue((static::$records['record_1']->get_creation_date() <= $date_obj));
    }

    protected function assertDateAtom($date)
    {
        return $this->assertRegExp('/\d{4}[-]\d{2}[-]\d{2}[T]\d{2}[:]\d{2}[:]\d{2}[+]\d{2}[:]\d{2}/', $date);
    }

    public function testGet_uuid()
    {
        $this->assertTrue(uuid::is_valid(static::$records['record_1']->get_uuid()));
    }

    public function testGet_modification_date()
    {
        $date_obj = new DateTime();
        $this->assertTrue((static::$records['record_1']->get_creation_date() instanceof DateTime));
        $this->assertTrue((static::$records['record_1']->get_creation_date() <= $date_obj));
    }

    public function testGet_number()
    {
        static::$records['record_1']->set_number(24);
        $this->assertEquals(24, static::$records['record_1']->get_number());
        static::$records['record_1']->set_number(42);
        $this->assertEquals(42, static::$records['record_1']->get_number());
        static::$records['record_1']->set_number(0);
        $this->assertEquals(0, static::$records['record_1']->get_number());
        static::$records['record_1']->set_number(null);
        $this->assertEquals(0, static::$records['record_1']->get_number());
    }

    public function testSet_number()
    {
        $this->testGet_number();
    }

    public function testSet_type()
    {
        try {
            static::$records['record_1']->set_type('jambon');
            $this->fail();
        } catch (Exception $e) {

        }
        $old_type = static::$records['record_1']->get_type();
        static::$records['record_1']->set_type('video');
        $this->assertEquals('video', static::$records['record_1']->get_type());
        static::$records['record_1']->set_type($old_type);
        $this->assertEquals($old_type, static::$records['record_1']->get_type());
    }

    public function testIs_grouping()
    {
        $this->assertFalse(static::$records['record_1']->is_grouping());
        $this->assertTrue(static::$records['record_story_1']->is_grouping());
    }

    public function testGet_base_id()
    {
        $this->assertTrue(is_int(static::$records['record_1']->get_base_id()));
        $this->assertEquals(self::$collection->get_base_id(), static::$records['record_1']->get_base_id());
        $this->assertTrue(is_int(static::$records['record_story_1']->get_base_id()));
        $this->assertEquals(self::$collection->get_base_id(), static::$records['record_story_1']->get_base_id());
    }

    public function testGet_record_id()
    {
        $this->assertTrue(is_int(static::$records['record_1']->get_record_id()));
        $this->assertTrue(is_int(static::$records['record_story_1']->get_record_id()));
    }

    public function testGet_thumbnail()
    {
        $this->assertTrue((static::$records['record_1']->get_thumbnail() instanceof media_subdef));
    }

    public function testGet_embedable_medias()
    {
        $embeddables = static::$records['record_1']->get_embedable_medias();
        $this->assertTrue(is_array($embeddables));
        foreach ($embeddables as $subdef) {
            $this->assertInstanceOf('media_subdef', $subdef);
        }
    }

    public function testGet_status_icons()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_type()
    {
        $this->assertTrue(in_array(static::$records['record_1']->get_type(), array('video', 'audio', 'image', 'document', 'flash', 'unknown')));
    }

    public function testGet_formated_duration()
    {
        $this->assertTrue(strpos(static::$records['record_23']->get_formated_duration(), '00:17') === 0);
        $this->assertEquals('', static::$records['record_1']->get_formated_duration());
    }

    public function testGet_duration()
    {
        $this->assertEquals(17, round(static::$records['record_23']->get_duration()));
        $this->assertEquals(false, static::$records['record_1']->get_duration());
    }

    public function testGet_rollover_thumbnail()
    {
        $this->assertInstanceOf('media_subdef', static::$records['record_23']->get_rollover_thumbnail());
        $this->assertNull(static::$records['record_1']->get_rollover_thumbnail());
    }

    public function testGenerate_subdefs()
    {
        $this->markTestIncomplete();
    }

    public function testGet_sha256()
    {
        $this->assertNotNull(static::$records['record_1']->get_sha256());
        $this->assertRegExp('/[a-zA-Z0-9]{64}/', static::$records['record_1']->get_sha256());
        $this->assertNull(static::$records['record_story_1']->get_sha256());
    }

    public function testGet_mime()
    {
        $this->assertRegExp('/image\/\w+/', static::$records['record_1']->get_mime());
    }

    public function testGet_status()
    {
        $this->assertRegExp('/[01]{64}/', static::$records['record_1']->get_status());
    }

    public function testGet_subdef()
    {
        $this->assertInstanceOf('media_subdef', static::$records['record_1']->get_subdef('document'));
        $this->assertInstanceOf('media_subdef', static::$records['record_1']->get_subdef('preview'));
        $this->assertInstanceOf('media_subdef', static::$records['record_1']->get_subdef('thumbnail'));
        $this->assertInstanceOf('media_subdef', static::$records['record_23']->get_subdef('document'));
        $this->assertInstanceOf('media_subdef', static::$records['record_23']->get_subdef('preview'));
        $this->assertInstanceOf('media_subdef', static::$records['record_23']->get_subdef('thumbnail'));
        $this->assertInstanceOf('media_subdef', static::$records['record_23']->get_subdef('thumbnailGIF'));
    }

    public function testGet_subdefs()
    {
        $subdefs = static::$records['record_1']->get_subdefs();
        $this->assertTrue(is_array($subdefs));
        foreach ($subdefs as $subdef) {
            $this->assertInstanceOf('media_subdef', $subdef);
        }
    }

    /**
     * @todo Implement testGet_collection_logo().
     */
    public function testGet_collection_logo()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_technical_infos()
    {
        $this->assertTrue(is_array(static::$records['record_1']->get_technical_infos()));
    }

    public function testGet_caption()
    {
        $this->assertTrue((static::$records['record_1']->get_caption() instanceof caption_record));
    }

    public function testGet_original_name()
    {
        $this->assertEquals('test001.CR2', static::$records['record_1']->get_original_name());
    }

    public function testGet_title()
    {
        $this->assertEquals('test001.CR2', static::$records['record_1']->get_title());
        $this->assertEquals('test023.mp4', static::$records['record_23']->get_title());
    }

    public function testGet_preview()
    {
        $this->assertTrue((static::$records['record_1']->get_preview() instanceof media_subdef));
    }

    public function testHas_preview()
    {
        $this->assertTrue(static::$records['record_1']->has_preview());
    }

    public function testGet_serialize_key()
    {
        $this->assertTrue(static::$records['record_1']->get_serialize_key() == static::$records['record_1']->get_sbas_id() . '_' . static::$records['record_1']->get_record_id());
    }

    public function testGet_sbas_id()
    {
        $this->assertTrue(is_int(static::$records['record_1']->get_sbas_id()));
    }

    public function testSubstitute_subdef()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSet_metadatas()
    {

        $meta_structure_el = self::$collection->get_databox()->get_meta_structure()->get_elements();

        $current_caption = static::$records['record_1']->get_caption();

        $metadatas = array();

        foreach ($meta_structure_el as $meta_el) {
            $current_fields = $current_caption->get_fields(array($meta_el->get_name()));

            $field = null;

            if (count($current_fields) > 0) {
                $field = array_pop($current_fields);
            }

            if ($meta_el->is_multi()) {
                if ($field) {
                    foreach ($field->get_values() as $value) {
                        $metadatas[] = array(
                            'meta_struct_id' => $meta_el->get_id()
                            , 'meta_id'        => $value->getId()
                            , 'value'          => ''
                        );
                    }
                }

                $metadatas[] = array(
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'un'
                );
                $metadatas[] = array(
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'jeu'
                );
                $metadatas[] = array(
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'de'
                );
                $metadatas[] = array(
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => null
                    , 'value'          => 'test'
                );
            } else {
                $meta_id = null;

                if ($field) {
                    $meta_id = array_pop($field->get_values())->getId();
                }

                $metadatas[] = array(
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => $meta_id
                    , 'value'          => 'un premier jeu de test'
                );

                $metadatas[] = array(
                    'meta_struct_id' => $meta_el->get_id()
                    , 'meta_id'        => $meta_id
                    , 'value'          => 'un second jeu de test'
                );
            }
        }

        static::$records['record_1']->set_metadatas($metadatas, true);

        $caption = static::$records['record_1']->get_caption();



        foreach ($meta_structure_el as $meta_el) {
            $current_fields = $caption->get_fields(array($meta_el->get_name()));

            $this->assertEquals(1, count($current_fields));
            $field = $current_fields[0];

            $separator = $meta_el->get_separator();

            if (strlen($separator) > 0) {
                $separator = $separator[0];
            } else {
                $separator = '';
            }

            $multi_imploded = implode(' ' . $separator . ' ', array('un', 'jeu', 'de', 'test'));

            if ($meta_el->is_multi()) {
                $initial_values = array();
                foreach ($field->get_values() as $value) {
                    $initial_values[] = $value->getValue();
                }

                $this->assertEquals($multi_imploded, implode(' ' . $meta_el->get_separator() . ' ', $initial_values));
                $this->assertEquals($multi_imploded, $field->get_serialized_values());
            }
            else
                $this->assertEquals('un second jeu de test', $field->get_serialized_values());
        }
    }

    public function testReindex()
    {
        static::$records['record_1']->reindex();
        $sql = 'SELECT record_id FROM record
            WHERE (status & 7) IN (4,5,6) AND record_id = :record_id';
        $stmt = static::$records['record_1']->get_databox()->get_connection()->prepare($sql);

        $stmt->execute(array(':record_id' => static::$records['record_1']->get_record_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != static::$records['record_1']->get_record_id())
            $this->fail();
    }

    public function testRebuild_subdefs()
    {

        static::$records['record_1']->rebuild_subdefs();
        $sql = 'SELECT record_id
              FROM record
              WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
              AND record_id = :record_id';
        $stmt = static::$records['record_1']->get_databox()->get_connection()->prepare($sql);

        $stmt->execute(array(':record_id' => static::$records['record_1']->get_record_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != static::$records['record_1']->get_record_id())
            $this->fail();
    }

    public function testWrite_metas()
    {
        static::$records['record_1']->write_metas();
        $sql = 'SELECT record_id, coll_id, jeton
            FROM record WHERE (jeton & ' . JETON_WRITE_META . ' > 0)
            AND record_id = :record_id';
        $stmt = static::$records['record_1']->get_databox()->get_connection()->prepare($sql);

        $stmt->execute(array(':record_id' => static::$records['record_1']->get_record_id()));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            $this->fail();
        if ($row['record_id'] != static::$records['record_1']->get_record_id())
            $this->fail();
    }

    /**
     * @todo Implement testSet_binary_status().
     */
    public function testSet_binary_status()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_record_by_sha()
    {
        $tmp_records = record_adapter::get_record_by_sha(static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_sha256());
        $this->assertTrue(is_array($tmp_records));

        foreach ($tmp_records as $tmp_record) {
            $this->assertInstanceOf('record_adapter', $tmp_record);
            $this->assertEquals(static::$records['record_1']->get_sha256(), $tmp_record->get_sha256());
        }

        $tmp_records = record_adapter::get_record_by_sha(static::$records['record_1']->get_sbas_id(), static::$records['record_1']->get_sha256(), static::$records['record_1']->get_record_id());
        $this->assertTrue(is_array($tmp_records));
        $this->assertTrue(count($tmp_records) === 1);

        foreach ($tmp_records as $tmp_record) {
            $this->assertInstanceOf('record_adapter', $tmp_record);
            $this->assertEquals(static::$records['record_1']->get_sha256(), $tmp_record->get_sha256());
            $this->assertEquals(static::$records['record_1']->get_record_id(), $tmp_record->get_record_id());
        }
    }

    public function testGet_hd_file()
    {
        $this->assertInstanceOf('\SplFileInfo', static::$records['record_1']->get_hd_file());
    }

    /**
     * @todo Implement testLog_view().
     */
    public function testLog_view()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testRotate_subdefs()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testGet_container_baskets()
    {
        $em = self::$core->getEntityManager();

        $basket = $this->insertOneBasket();
        $this->assertInstanceOf('\Entities\Basket', $basket);

        /* @var $basket \Entities\Basket */
        $basket_element = new \Entities\BasketElement();
        $basket_element->setRecord(static::$records['record_1']);
        $basket_element->setBasket($basket);

        $em->persist($basket_element);

        $basket->addBasketElement($basket_element);
        $basket = $em->merge($basket);

        $em->flush();

        $found = $sselcont_id = false;

        $sbas_id = static::$records['record_1']->get_sbas_id();
        $record_id = static::$records['record_1']->get_record_id();

        foreach (static::$records['record_1']->get_container_baskets() as $c_basket) {
            if ($c_basket->getId() == $basket->getId()) {
                $found = true;
                foreach ($c_basket->getElements() as $b_el) {
                    if ($b_el->getRecord()->get_record_id() == $record_id && $b_el->getRecord()->get_sbas_id() == $sbas_id)
                        $sselcont_id = $b_el->getId();
                }
            }
        }


        if ( ! $found)
            $this->fail();
    }
}
