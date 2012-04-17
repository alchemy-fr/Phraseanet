<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class databox_fieldTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var databox_field
   */
  protected $object_mono;
  protected $object_multi;
  protected $databox;
  protected $name_mono;
  protected $name_multi;
  protected static $need_records = 1;

  public function setUp()
  {
    $this->databox = self::$record_1->get_databox();
    $this->name_mono = 'Field Test Mono';
    $this->name_multi = 'Field Test Multi';

    $this->object_mono = $this->databox->get_meta_structure()->get_element_by_name($this->name_mono);

    $this->object_multi = $this->databox->get_meta_structure()->get_element_by_name($this->name_multi);

    if(!$this->object_mono instanceof databox_field)
      $this->object_mono = databox_field::create($this->databox, $this->name_mono);
    if(!$this->object_multi instanceof databox_field)
    {
      $this->object_multi = databox_field::create($this->databox, $this->name_multi);
      $this->object_multi->set_multi(true)->save();
    }
  }

  public function tearDown()
  {
    if($this->object_mono instanceof databox_field)
      $this->object_mono->delete();
    if($this->object_multi instanceof databox_field)
      $this->object_multi->delete();

    $extra = $this->databox->get_meta_structure()->get_element_by_name('Bonoboyoyo');
    if($extra instanceof databox_field)
      $extra->delete();
  }

  public function testGet_instance()
  {
    $instance = databox_field::get_instance($this->databox, $this->object_mono->get_id());
    $this->assertEquals($this->object_mono->get_id(), $instance->get_id());

    $instance = databox_field::get_instance($this->databox, $this->object_multi->get_id());
    $this->assertEquals($this->object_multi->get_id(), $instance->get_id());
  }

  /**
   * @todo Implement testSet_databox().
   */
  public function testSet_databox()
  {
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  public function testGet_connection()
  {
    $this->assertInstanceOf('\connection_pdo', $this->object_mono->get_connection());
    $this->assertInstanceOf('\connection_pdo', $this->object_multi->get_connection());
  }

  public function testGet_databox()
  {
    $this->assertInstanceOf('\databox', $this->object_mono->get_databox());
    $this->assertEquals(self::$record_1->get_databox()->get_sbas_id(), $this->object_mono->get_databox()->get_sbas_id());
    $this->assertInstanceOf('\databox', $this->object_multi->get_databox());
    $this->assertEquals(self::$record_1->get_databox()->get_sbas_id(), $this->object_multi->get_databox()->get_sbas_id());
  }

  /**
   * @todo Implement testDelete().
   */
  public function testDelete()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testSave().
   */
  public function testSave()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  public function testSet_name()
  {
    $name = 'Eléphant';
    $this->object_mono->set_name($name);
    $this->assertEquals('Elephant', $this->object_mono->get_name());

    $name = '0!èEléphant ';
    $this->object_mono->set_name($name);
    $this->assertEquals('eElephant', $this->object_mono->get_name());

    $name = 'Gaston';
    $this->object_mono->set_name($name);
    $this->assertEquals('Gaston', $this->object_mono->get_name());

    try
    {
      $this->object_mono->set_name('');
      $this->fail();
    }
    catch (Exception $e)
    {
      $this->assertTrue(true, 'test passed');
    }

    try
    {
      $this->object_mono->set_name('éà');
      $this->assertEquals('ea', $this->object_mono->get_name());
    }
    catch (Exception $e)
    {

    }
  }

  /**
   * @todo Implement testLoad_class_from_xpath().
   */
  public function testLoad_class_from_xpath()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  public function testSet_source()
  {
    $source = '/rdf:RDF/rdf:Description/PHRASEANET:tf-filename';

    $this->object_mono->set_source($source);
    $this->object_multi->set_source($source);

    $this->assertEquals($source, $this->object_mono->get_source()->get_source());
    $this->assertEquals($source, $this->object_multi->get_source()->get_source());

    $this->object_mono->set_source(null);
    $this->object_multi->set_source(null);

    $this->assertInstanceOf('\metadata_Interface', $this->object_mono->get_source());
    $this->assertInstanceOf('\metadata_Interface', $this->object_multi->get_source());
    $this->assertEquals('', $this->object_mono->get_source()->get_source());
    $this->assertEquals('', $this->object_multi->get_source()->get_source());
  }

  public function testGet_source()
  {
    $this->assertInstanceOf('\metadata_Interface', $this->object_mono->get_source());
    $this->assertInstanceOf('\metadata_Interface', $this->object_multi->get_source());
  }

  /**
   * @todo Implement testGet_dces_element().
   */
  public function testGet_dces_element()
  {
    $this->assertNull($this->object_mono->get_dces_element());
    $this->assertNull($this->object_multi->get_dces_element());
  }

  public function testSet_dces_element()
  {
    $this->object_mono->set_dces_element(new \databox_Field_DCES_Contributor());
    $this->object_multi->set_dces_element(new \databox_Field_DCES_Format());

    $this->assertInstanceOf('\databox_Field_DCESAbstract', $this->object_mono->get_dces_element());
    $this->assertInstanceOf('\databox_Field_DCESAbstract', $this->object_multi->get_dces_element());

    $this->object_multi->set_dces_element(null);
    $this->assertNull($this->object_multi->get_dces_element());
  }

  public function testSet_indexable()
  {
    $this->object_mono->set_indexable(false);
    $this->assertFalse($this->object_mono->is_indexable());
    $this->object_mono->set_indexable(true);
    $this->assertTrue($this->object_mono->is_indexable());
  }

  public function testSet_readonly()
  {
    $this->object_mono->set_readonly(false);
    $this->assertFalse($this->object_mono->is_readonly());
    $this->object_mono->set_readonly(true);
    $this->assertTrue($this->object_mono->is_readonly());
  }

  public function testSet_required()
  {
    $this->object_mono->set_required(false);
    $this->assertFalse($this->object_mono->is_required());
    $this->object_mono->set_required(true);
    $this->assertTrue($this->object_mono->is_required());
  }

  public function testSet_business()
  {
    $this->object_mono->set_business(false);
    $this->assertFalse($this->object_mono->isBusiness());
    $this->object_mono->set_business(true);
    $this->assertTrue($this->object_mono->isBusiness());
  }

  public function testSet_multi()
  {
    $this->object_mono->set_multi(false);
    $this->assertFalse($this->object_mono->is_multi());
    $this->object_mono->set_multi(true);
    $this->assertTrue($this->object_mono->is_multi());
  }

  public function testSet_report()
  {
    $this->object_mono->set_report(false);
    $this->assertFalse($this->object_mono->is_report());
    $this->object_mono->set_report(true);
    $this->assertTrue($this->object_mono->is_report());
  }

  public function testSet_type()
  {
    $this->object_mono->set_type('date');
    $this->assertEquals('date', $this->object_mono->get_type());
    $this->object_mono->set_type('text');
    $this->assertEquals('text', $this->object_mono->get_type());
  }

  public function testSet_tbranch()
  {
    $this->object_mono->set_tbranch('newBranche');
    $this->assertEquals('newBranche', $this->object_mono->get_tbranch());
    $this->object_mono->set_tbranch(null);
    $this->assertNull($this->object_mono->get_tbranch());
  }

  public function testSet_separator()
  {
    $this->assertEquals('', $this->object_mono->get_separator());
    $this->assertEquals(';', $this->object_multi->get_separator());

    $this->object_mono->set_separator(';.:');
    $this->object_multi->set_separator(';.:');

    $this->assertEquals('', $this->object_mono->get_separator());
    $this->assertEquals(';.:', $this->object_multi->get_separator());

    $this->object_multi->set_separator('.:-');
    $this->assertEquals('.:-;', $this->object_multi->get_separator());
  }

  public function testSet_thumbtitle()
  {
    $this->object_mono->set_thumbtitle(true);
    $this->assertTrue($this->object_mono->get_thumbtitle());
    $this->object_mono->set_thumbtitle('fr');
    $this->assertEquals('fr', $this->object_mono->get_thumbtitle());
    $this->object_mono->set_thumbtitle(false);
    $this->assertFalse($this->object_mono->get_thumbtitle());
  }

  public function testGet_thumbtitle()
  {
    $this->assertNull($this->object_mono->get_thumbtitle());
    $this->assertNull($this->object_multi->get_thumbtitle());
  }

  public function testGet_id()
  {
    $this->assertTrue(is_int($this->object_mono->get_id()));
    $this->assertTrue(is_int($this->object_multi->get_id()));
  }

  public function testGet_type()
  {
    $this->assertEquals('string', $this->object_mono->get_type());
    $this->assertEquals('string', $this->object_multi->get_type());
  }

  public function testGet_tbranch()
  {
    $this->assertEquals('', $this->object_mono->get_tbranch());
    $this->assertEquals('', $this->object_multi->get_tbranch());
  }

  public function testGet_separator()
  {
    $this->assertEquals('', $this->object_mono->get_separator());
    $this->assertEquals(';', $this->object_multi->get_separator());
  }

  public function testIs_indexable()
  {
    $this->assertTrue($this->object_mono->is_indexable());
    $this->assertTrue($this->object_multi->is_indexable());
  }

  public function testIs_readonly()
  {
    $this->assertFalse($this->object_mono->is_readonly());
    $this->assertFalse($this->object_multi->is_readonly());
  }

  public function testIs_required()
  {
    $this->assertFalse($this->object_mono->is_required());
    $this->assertFalse($this->object_multi->is_required());
  }

  public function testIs_multi()
  {
    $this->assertFalse($this->object_mono->is_multi());
    $this->assertTrue($this->object_multi->is_multi());
  }

  public function testIs_report()
  {
    $this->assertTrue($this->object_mono->is_report());
    $this->assertTrue($this->object_multi->is_report());
  }

  public function testGet_name()
  {
    $this->assertEquals(str_replace(' ', '', $this->name_mono), $this->object_mono->get_name());
    $this->assertEquals(str_replace(' ', '', $this->name_multi), $this->object_multi->get_name());
  }

  public function testGet_metadata_source()
  {
    $this->assertEquals('', $this->object_mono->get_metadata_source());
    $this->assertEquals('', $this->object_multi->get_metadata_source());

    $source = '/rdf:RDF/rdf:Description/PHRASEANET:tf-filename';

    $this->object_mono->set_source($source);
    $this->object_multi->set_source($source);

    $this->assertEquals($source, $this->object_mono->get_metadata_source());
    $this->assertEquals($source, $this->object_multi->get_metadata_source());
  }

  public function testGet_metadata_namespace()
  {
    $this->assertEquals('NoSource', $this->object_mono->get_metadata_namespace());
    $this->assertEquals('NoSource', $this->object_multi->get_metadata_namespace());

    $source = '/rdf:RDF/rdf:Description/PHRASEANET:tf-filename';

    $this->object_mono->set_source($source);
    $this->object_multi->set_source($source);

    $this->assertEquals('PHRASEANET', $this->object_mono->get_metadata_namespace());
    $this->assertEquals('PHRASEANET', $this->object_multi->get_metadata_namespace());
  }

  public function testGet_metadata_tagname()
  {
    $this->assertEquals('NoSource', $this->object_mono->get_metadata_tagname());
    $this->assertEquals('NoSource', $this->object_multi->get_metadata_tagname());

    $source = '/rdf:RDF/rdf:Description/PHRASEANET:tf-filename';

    $this->object_mono->set_source($source);
    $this->object_multi->set_source($source);

    $this->assertEquals('tf-filename', $this->object_mono->get_metadata_tagname());
    $this->assertEquals('tf-filename', $this->object_multi->get_metadata_tagname());
  }

  public function testIs_on_error()
  {
    $this->assertFalse($this->object_mono->is_on_error());
    $this->assertFalse($this->object_multi->is_on_error());
  }

  public function testRenameField()
  {
    $AddedValue = 'scalar value';

    self::$record_1->set_metadatas(array(
      array(
        'meta_id' => null,
        'meta_struct_id' => $this->object_mono->get_id(),
        'value'=> $AddedValue
      )
    ));

    $this->object_mono->set_name('Bonobo yoyo')->save();

    $value = array_pop(self::$record_1->get_caption()->get_field('Bonoboyoyo')->get_values());
    $this->assertEquals($value->getValue(), $AddedValue);
  }

  public function testChangeMulti()
  {
    $AddedValue_1 = 'scalar value 1';
    $AddedValue_2 = 'scalar value 2';

    self::$record_1->set_metadatas(array(
      array(
        'meta_id' => null,
        'meta_struct_id' => $this->object_multi->get_id(),
        'value'=> $AddedValue_1
      ),
      array(
        'meta_id' => null,
        'meta_struct_id' => $this->object_multi->get_id(),
        'value'=> $AddedValue_2
      )
    ));

    $this->assertEquals(2, count(self::$record_1->get_caption()->get_field(str_replace(' ', '', $this->name_multi))->get_values()));

    $this->object_multi->set_multi(false)->save();

    $this->assertEquals(1, count(self::$record_1->get_caption()->get_field(str_replace(' ', '', $this->name_multi))->get_values()));
  }

  /**
   * @todo Implement testCreate().
   */
  public function testCreate()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement test__sleep().
   */
  public function test__sleep()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement test__wakeup().
   */
  public function test__wakeup()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_cache_key().
   */
  public function testGet_cache_key()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGet_data_from_cache().
   */
  public function testGet_data_from_cache()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testSet_data_to_cache().
   */
  public function testSet_data_to_cache()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testDelete_data_from_cache().
   */
  public function testDelete_data_from_cache()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

}
