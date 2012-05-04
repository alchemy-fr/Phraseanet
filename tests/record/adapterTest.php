<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class record_adapterTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

  /**
   * @var record_adapter
   */
  protected static $grouping;
  protected static $need_records = true;
  protected static $need_story   = true;
  protected static $need_subdefs = true;

  public static function setUpBeforeClass()
  {
    parent::setUpBeforeClass();
    $system_file = self::$record_1->get_hd_file();
    $databox     = self::$record_1->get_databox();
    $metadatas   = $system_file->extract_metadatas($databox->get_meta_structure());
    static::$record_1->set_metadatas($metadatas['metadatas']);

    $databox     = self::$record_23->get_databox();
    $system_file = self::$record_23->get_hd_file();
    $metadatas   = $system_file->extract_metadatas($databox->get_meta_structure());
    static::$record_23->set_metadatas($metadatas['metadatas']);


    /**
     * Reset thumbtitle in order to have consistent tests (testGet_title)
     */
    foreach(static::$record_1->get_databox()->get_meta_structure() as $databox_field)
    {

      /* @var $databox_field \databox_field */
      $databox_field->set_thumbtitle(false)->save();
    }


    $system_file = new system_file(__DIR__ . '/../testfiles/cestlafete.jpg');
  }

  public static function tearDownAfterClass()
  {
    parent::tearDownAfterClass();
  }

  /**
     *  Check whether a record is delete from order_elements when
     *  record::delete is call
     * @covers \record_adapter
     */
    public function testSetExport()
    {
        $recordsf = new system_file(__DIR__ . '/../testfiles/test001.CR2');
        $record = record_adapter::create(self::$collection, $recordsf);

        $basket = new \Entities\Basket();

        $basket->setName('hello');
        $basket->setOwner(self::$user);
        $basket->setDescription('hello');

        $em = self::$core->getEntityManager();

        $basketElement = new \Entities\BasketElement();

        $basketElement->setRecord($record);
        $basketElement->setBasket($basket);

        $em->persist($basketElement);

        $basket->addBasketElement($basketElement);

        $em->persist($basket);
        $em->flush();

        $export = new set_exportorder(self::$record_1->get_serialize_key(), $basket->getId());

        $orderId = $export->order_available_elements(self::$user->get_id(), 'ahaha', '+2 hours');

        $record->delete();

        try {
            $order = new set_order($orderId);
        } catch (\Exception $e) {
            $this->fail('should not raise an exception' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile()  . ' ' . $e->getTraceAsString());
        }
    }

  public function testGet_creation_date()
  {
    $date_obj = new DateTime();
    $this->assertTrue((static::$record_1->get_creation_date() instanceof DateTime));
    $this->assertTrue((static::$record_1->get_creation_date() <= $date_obj));
  }

  protected function assertDateAtom($date)
  {
    return $this->assertRegExp('/\d{4}[-]\d{2}[-]\d{2}[T]\d{2}[:]\d{2}[:]\d{2}[+]\d{2}[:]\d{2}/', $date);
  }

  public function testGet_uuid()
  {
    $this->assertTrue(uuid::is_valid(static::$record_1->get_uuid()));
  }

  public function testGet_modification_date()
  {
    $date_obj = new DateTime();
    $this->assertTrue((static::$record_1->get_creation_date() instanceof DateTime));
    $this->assertTrue((static::$record_1->get_creation_date() <= $date_obj));
  }

  public function testGet_number()
  {
    self::$record_1->set_number(24);
    $this->assertEquals(24, self::$record_1->get_number());
    self::$record_1->set_number(42);
    $this->assertEquals(42, self::$record_1->get_number());
    self::$record_1->set_number(0);
    $this->assertEquals(0, self::$record_1->get_number());
    self::$record_1->set_number(null);
    $this->assertEquals(0, self::$record_1->get_number());
  }

  public function testSet_number()
  {
    $this->testGet_number();
  }

  public function testSet_type()
  {
    try
    {
      self::$record_1->set_type('jambon');
      $this->fail();
    }
    catch (Exception $e)
    {

    }
    $old_type = self::$record_1->get_type();
    self::$record_1->set_type('video');
    $this->assertEquals('video', self::$record_1->get_type());
    self::$record_1->set_type($old_type);
    $this->assertEquals($old_type, self::$record_1->get_type());
  }

  public function testIs_grouping()
  {
    $this->assertFalse(self::$record_1->is_grouping());
    $this->assertTrue(self::$story_1->is_grouping());
  }

  public function testGet_base_id()
  {
    $this->assertTrue(is_int(static::$record_1->get_base_id()));
    $this->assertEquals(self::$collection->get_base_id(), static::$record_1->get_base_id());
    $this->assertTrue(is_int(self::$story_1->get_base_id()));
    $this->assertEquals(self::$collection->get_base_id(), self::$story_1->get_base_id());
  }

  public function testGet_record_id()
  {
    $this->assertTrue(is_int(static::$record_1->get_record_id()));
    $this->assertTrue(is_int(self::$story_1->get_record_id()));
  }

  public function testGet_thumbnail()
  {
    $this->assertTrue((static::$record_1->get_thumbnail() instanceof media_subdef));
  }

  public function testGet_embedable_medias()
  {
    $embeddables = self::$record_1->get_embedable_medias();
    $this->assertTrue(is_array($embeddables));
    foreach ($embeddables as $subdef)
    {
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
    $this->assertTrue(in_array(static::$record_1->get_type(), array('video', 'audio', 'image', 'document', 'flash', 'unknown')));
  }

  public function testGet_formated_duration()
  {
    $this->assertTrue(strpos(self::$record_23->get_formated_duration(), '00:17') === 0);
    $this->assertEquals('', self::$record_1->get_formated_duration());
  }

  public function testGet_duration()
  {
    $this->assertEquals(17, round(self::$record_23->get_duration()));
    $this->assertEquals(false, self::$record_1->get_duration());
  }

  public function testGet_rollover_thumbnail()
  {
    $this->assertInstanceOf('media_subdef', self::$record_23->get_rollover_thumbnail());
    $this->assertNull(self::$record_1->get_rollover_thumbnail());
  }

  public function testGenerate_subdefs()
  {

  }

  public function testGet_sha256()
  {
    $this->assertNotNull(static::$record_1->get_sha256());
    $this->assertRegExp('/[a-zA-Z0-9]{64}/', static::$record_1->get_sha256());
    $this->assertNull(self::$story_1->get_sha256());
  }

  public function testGet_mime()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $found  = $coll   = false;
    foreach ($appbox->get_databoxes() as $databox)
    {
      foreach ($databox->get_collections() as $collection)
      {
        $found = true;
        $coll  = $collection;
        break;
      }
      if ($found)
        break;
    }
    if (!($coll instanceof collection))
      $this->fail('Unable to find a collection');

    $record = record_adapter::create($coll, new system_file(__DIR__ . '/../testfiles/cestlafete.jpg'));

    $this->assertEquals('image/jpeg', $record->get_mime());
    $record->delete();
  }

  public function testGet_status()
  {
    $this->assertRegExp('/[01]{64}/', static::$record_1->get_status());
  }

  public function testGet_subdef()
  {
    $this->assertInstanceOf('media_subdef', self::$record_1->get_subdef('document'));
    $this->assertInstanceOf('media_subdef', self::$record_1->get_subdef('preview'));
    $this->assertInstanceOf('media_subdef', self::$record_1->get_subdef('thumbnail'));
    $this->assertInstanceOf('media_subdef', self::$record_23->get_subdef('document'));
    $this->assertInstanceOf('media_subdef', self::$record_23->get_subdef('preview'));
    $this->assertInstanceOf('media_subdef', self::$record_23->get_subdef('thumbnail'));
    $this->assertInstanceOf('media_subdef', self::$record_23->get_subdef('thumbnailGIF'));
  }

  public function testGet_subdefs()
  {
    $subdefs = static::$record_1->get_subdefs();
    $this->assertTrue(is_array($subdefs));
    foreach ($subdefs as $subdef)
    {
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
    $this->assertTrue(is_array(static::$record_1->get_technical_infos()));
  }

  public function testGet_caption()
  {
    $this->assertTrue((static::$record_1->get_caption() instanceof caption_record));
  }

  public function testGet_original_name()
  {
    $this->assertTrue(static::$record_1->get_original_name() === self::$record_sf_1->getFilename());
  }

  public function testGet_title()
  {
    $this->assertEquals(static::$record_sf_1->getFilename(), static::$record_1->get_title());
    $this->assertEquals(static::$record_sf_23->getFilename(), static::$record_23->get_title());
  }

  public function testGet_preview()
  {
    $this->assertTrue((static::$record_1->get_preview() instanceof media_subdef));
  }

  public function testHas_preview()
  {
    $this->assertTrue(self::$record_1->has_preview());
  }

  public function testGet_serialize_key()
  {
    $this->assertTrue(static::$record_1->get_serialize_key() == static::$record_1->get_sbas_id() . '_' . static::$record_1->get_record_id());
  }

  public function testGet_sbas_id()
  {
    $this->assertTrue(is_int(static::$record_1->get_sbas_id()));
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

    $current_caption = self::$record_1->get_caption();

    $metadatas = array();

    foreach ($meta_structure_el as $meta_el)
    {
      $current_fields = $current_caption->get_fields(array($meta_el->get_name()));

      $field = null;

      if (count($current_fields) > 0)
      {
        $field = array_pop($current_fields);
      }

      if($meta_el->is_multi())
      {
        if($field)
        {
          foreach($field->get_values() as $value)
          {
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
      }
      else
      {
        $meta_id = null;

        if($field)
        {
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

    self::$record_1->set_metadatas($metadatas, true);

    $caption = self::$record_1->get_caption();



    foreach ($meta_structure_el as $meta_el)
    {
      $current_fields = $caption->get_fields(array($meta_el->get_name()));

      $this->assertEquals(1, count($current_fields));
      $field = $current_fields[0];

      $separator = $meta_el->get_separator();

      if(strlen($separator) > 0)
      {
        $separator = $separator[0];
      }
      else
      {
        $separator = '';
      }

      $multi_imploded = implode(' ' . $separator . ' ', array('un', 'jeu', 'de', 'test'));

      if ($meta_el->is_multi())
      {
        $initial_values = array();
        foreach($field->get_values() as $value)
        {
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
    self::$record_1->reindex();
    $sql  = 'SELECT record_id FROM record
            WHERE (status & 7) IN (4,5,6) AND record_id = :record_id';
    $stmt = self::$record_1->get_databox()->get_connection()->prepare($sql);

    $stmt->execute(array(':record_id' => self::$record_1->get_record_id()));
    $row         = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      $this->fail();
    if ($row['record_id'] != self::$record_1->get_record_id())
      $this->fail();
  }

  public function testRebuild_subdefs()
  {

    self::$record_1->rebuild_subdefs();
    $sql  = 'SELECT record_id
              FROM record
              WHERE jeton & ' . JETON_MAKE_SUBDEF . ' > 0
              AND record_id = :record_id';
    $stmt = self::$record_1->get_databox()->get_connection()->prepare($sql);

    $stmt->execute(array(':record_id' => self::$record_1->get_record_id()));
    $row         = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      $this->fail();
    if ($row['record_id'] != self::$record_1->get_record_id())
      $this->fail();
  }

  public function testWrite_metas()
  {
    self::$record_1->write_metas();
    $sql  = 'SELECT record_id, coll_id, jeton
            FROM record WHERE (jeton & ' . JETON_WRITE_META . ' > 0)
            AND record_id = :record_id';
    $stmt = self::$record_1->get_databox()->get_connection()->prepare($sql);

    $stmt->execute(array(':record_id' => self::$record_1->get_record_id()));
    $row         = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$row)
      $this->fail();
    if ($row['record_id'] != self::$record_1->get_record_id())
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
    $tmp_records = record_adapter::get_record_by_sha(self::$record_1->get_sbas_id(), self::$record_1->get_sha256());
    $this->assertTrue(is_array($tmp_records));

    foreach ($tmp_records as $tmp_record)
    {
      $this->assertInstanceOf('record_adapter', $tmp_record);
      $this->assertEquals(self::$record_1->get_sha256(), $tmp_record->get_sha256());
    }

    $tmp_records = record_adapter::get_record_by_sha(self::$record_1->get_sbas_id(), self::$record_1->get_sha256(), self::$record_1->get_record_id());
    $this->assertTrue(is_array($tmp_records));
    $this->assertTrue(count($tmp_records) === 1);

    foreach ($tmp_records as $tmp_record)
    {
      $this->assertInstanceOf('record_adapter', $tmp_record);
      $this->assertEquals(self::$record_1->get_sha256(), $tmp_record->get_sha256());
      $this->assertEquals(self::$record_1->get_record_id(), $tmp_record->get_record_id());
    }
  }

  public function testGet_hd_file()
  {
    $this->assertInstanceOf('system_file', self::$record_1->get_hd_file());
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

  /**
   * @todo Implement testGet_container_baskets().
   */
  public function testGet_container_baskets()
  {
    $em = self::$core->getEntityManager();

    $basket = $this->insertOneBasket();
    $this->assertInstanceOf('\Entities\Basket', $basket);

    /* @var $basket \Entities\Basket */
    $basket_element = new \Entities\BasketElement();
    $basket_element->setRecord(self::$record_1);
    $basket_element->setBasket($basket);

    $em->persist($basket_element);

    $basket->addBasketElement($basket_element);
    $basket = $em->merge($basket);

    $em->flush();

    $found       = $sselcont_id = false;

    $sbas_id   = self::$record_1->get_sbas_id();
    $record_id = self::$record_1->get_record_id();

    foreach (self::$record_1->get_container_baskets() as $c_basket)
    {
      if ($c_basket->getId() == $basket->getId())
      {
        $found = true;
        foreach ($c_basket->getElements() as $b_el)
        {
          if ($b_el->getRecord()->get_record_id() == $record_id && $b_el->getRecord()->get_sbas_id() == $sbas_id)
            $sselcont_id = $b_el->getId();
        }
      }
    }


    if (!$found)
      $this->fail();
  }

}

