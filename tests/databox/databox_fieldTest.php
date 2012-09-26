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

    public function setUp()
    {
        parent::setUp();
        $this->databox = self::$DI['record_1']->get_databox();
        $this->name_mono = 'Field Test Mono';
        $this->name_multi = 'Field Test Multi';

        $this->object_mono = $this->databox->get_meta_structure()->get_element_by_name($this->name_mono);

        $this->object_multi = $this->databox->get_meta_structure()->get_element_by_name($this->name_multi);

        if ( ! $this->object_mono instanceof databox_field) {
            $this->object_mono = databox_field::create(self::$DI['app'], $this->databox, $this->name_mono, false);
        }
        if ( ! $this->object_multi instanceof databox_field) {
            $this->object_multi = databox_field::create(self::$DI['app'], $this->databox, $this->name_multi, true);
        }
    }

    public function tearDown()
    {
        if ($this->object_mono instanceof databox_field) {
            $this->object_mono->delete();
        }
        if ($this->object_multi instanceof databox_field) {
            $this->object_multi->delete();
        }

        $extra = $this->databox->get_meta_structure()->get_element_by_name('Bonoboyoyo');
        if ($extra instanceof databox_field) {
            $extra->delete();
        }

        parent::tearDown();
    }

    public function testGet_instance()
    {
        $instance = databox_field::get_instance(self::$DI['app'], $this->databox, $this->object_mono->get_id());
        $this->assertEquals($this->object_mono->get_id(), $instance->get_id());

        $instance = databox_field::get_instance(self::$DI['app'], $this->databox, $this->object_multi->get_id());
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
        $this->assertEquals(self::$DI['record_1']->get_databox()->get_sbas_id(), $this->object_mono->get_databox()->get_sbas_id());
        $this->assertInstanceOf('\databox', $this->object_multi->get_databox());
        $this->assertEquals(self::$DI['record_1']->get_databox()->get_sbas_id(), $this->object_multi->get_databox()->get_sbas_id());
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

        try {
            $this->object_mono->set_name('');
            $this->fail();
        } catch (Exception $e) {
            $this->assertTrue(true, 'test passed');
        }

        try {
            $this->object_mono->set_name('éà');
            $this->assertEquals('ea', $this->object_mono->get_name());
        } catch (Exception $e) {

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

    public function testSet_tag()
    {
        $source = \databox_field::loadClassFromTagName('Phraseanet:tf-basename');

        $this->object_mono->set_tag($source);
        $this->object_multi->set_tag($source);

        $this->assertEquals($source, $this->object_mono->get_tag());
        $this->assertEquals($source, $this->object_multi->get_tag());

        $this->object_mono->set_tag(null);
        $this->object_multi->set_tag(null);

        $this->assertEquals(new \Alchemy\Phrasea\Metadata\Tag\Nosource(), $this->object_mono->get_tag());
        $this->assertEquals(new \Alchemy\Phrasea\Metadata\Tag\Nosource(), $this->object_multi->get_tag());
    }

    public function testGet_tag()
    {
        $this->assertInstanceOf('\\PHPExiftool\\Driver\\TagInterface', $this->object_mono->get_tag());
        $this->assertInstanceOf('\\PHPExiftool\\Driver\\TagInterface', $this->object_multi->get_tag());
    }

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

    public function testIs_on_error()
    {
        $this->assertFalse($this->object_mono->is_on_error());
        $this->assertFalse($this->object_multi->is_on_error());
    }

    public function testRenameField()
    {
        $AddedValue = 'scalar value';

        self::$DI['record_1']->set_metadatas(array(
            array(
                'meta_id'        => null,
                'meta_struct_id' => $this->object_mono->get_id(),
                'value'          => $AddedValue
            )
        ));

        $this->object_mono->set_name('Bonobo yoyo')->save();

        $value = array_pop(self::$DI['record_1']->get_caption()->get_field('Bonoboyoyo')->get_values());
        $this->assertEquals($value->getValue(), $AddedValue);
    }

}
