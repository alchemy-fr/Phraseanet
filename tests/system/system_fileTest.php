<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class system_fileTest extends PhraseanetPHPUnitAbstract
{

  /**
   * @var system_file
   */
  protected $object = array();

  public function setUp()
  {
    $this->objects['indd'] = new system_file(__DIR__ . '/../../lib/vendor/exiftool/t/images/InDesign.indd');
    $this->objects['wav'] = new system_file(__DIR__ . '/../testfiles/test012.wav');
    $this->objects['jpg'] = new system_file(__DIR__ . '/../../lib/vendor/exiftool/t/images/Casio.jpg');
  }

  public function testGet_mime()
  {
    $this->assertEquals('application/octet-stream', $this->objects['indd']->get_mime());
    $this->assertEquals('audio/x-wav', $this->objects['wav']->get_mime());
    $this->assertEquals('image/jpeg', $this->objects['jpg']->get_mime());
  }

  public function testIs_raw_image()
  {
    $this->assertFalse($this->objects['indd']->is_raw_image());
    $this->assertFalse($this->objects['wav']->is_raw_image());
    $this->assertFalse($this->objects['jpg']->is_raw_image());
  }

  public function testGet_extension()
  {
    $this->assertEquals('indd', $this->objects['indd']->get_extension());
    $this->assertEquals('wav', $this->objects['wav']->get_extension());
    $this->assertEquals('jpg', $this->objects['jpg']->get_extension());
  }

  public function testGet_sha256()
  {
    $this->assertEquals('50a49257863f510b4e7f0de795884a89aeefdd701069db414a472f0ede7b0c98', $this->objects['indd']->get_sha256());
    $this->assertEquals('690e00c90a97ea24a2bd69abe410dc0fc3b6b69ddc20b1d572d0a953a4617eed', $this->objects['wav']->get_sha256());
    $this->assertEquals('c08fed0e193a1549c130d79814d30a3ac3fcd81980e261c4947ff45691628f92', $this->objects['jpg']->get_sha256());
  }

  public function testGet_technical_datas()
  {
    $technical_datas = $this->objects['jpg']->get_technical_datas();
    $this->assertArrayHasKey(system_file::TC_DATAS_WIDTH, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_HEIGHT, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_CHANNELS, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_COLORDEPTH, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_MIMETYPE, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_FILESIZE, $technical_datas);
    $technical_datas = $this->objects['indd']->get_technical_datas();
    $this->assertArrayHasKey(system_file::TC_DATAS_MIMETYPE, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_FILESIZE, $technical_datas);

    $registry = registry::get_instance();
    if (!is_executable($registry->get('GV_mplayer')))
      $this->markTestSkipped('MPlayer is not configured');
    $technical_datas = $this->objects['wav']->get_technical_datas();
    $this->assertArrayHasKey(system_file::TC_DATAS_AUDIOBITRATE, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_AUDIOSAMPLERATE, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_AUDIOCODEC, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_DURATION, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_MIMETYPE, $technical_datas);
    $this->assertArrayHasKey(system_file::TC_DATAS_FILESIZE, $technical_datas);
  }

  public function testGet_phrasea_type()
  {
    $this->assertEquals('unknown', $this->objects['indd']->get_phrasea_type());
    $this->assertEquals('audio', $this->objects['wav']->get_phrasea_type());
    $this->assertEquals('image', $this->objects['jpg']->get_phrasea_type());
  }

  public function testGetPath()
  {
    $supposed = __DIR__ . '/../../lib/vendor/exiftool/t/images/';
    $this->assertEquals($supposed, $this->objects['indd']->getPath());
  }

  /**
   * @todo Implement testHas_uuid().
   */
  public function testHas_uuid()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testRead_uuid().
   */
  public function testRead_uuid()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testWrite_uuid().
   */
  public function testWrite_uuid()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testIs_new_in_base().
   */
  public function testIs_new_in_base()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testGenerate_and_write().
   */
  public function testGenerate_and_write()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  /**
   * @todo Implement testWrite().
   */
  public function testWrite()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
            'This test has not been implemented yet.'
    );
  }

  public function testMkdir()
  {
    $path = __DIR__ . '/test/dir/to/create/';
    if (is_dir($path))
    {
      $this->fail('unable to create directory : directory already extists');
    }
    system_file::mkdir($path);
    $this->assertTrue(is_dir($path));
    rmdir($path);
    $path = dirname($path);
    rmdir($path);
    $path = dirname($path);
    rmdir($path);
    $path = dirname($path);
    rmdir(__DIR__ . '/test');
    $path = dirname($path);
  }

  public function testChmod()
  {
    $file = __DIR__ . '/../testfiles/cestlafete.jpg';
    $dir = __DIR__ . '/testchmod';
    system_file::mkdir($dir);

    chmod($file, 0700);
    chmod($dir, 0700);
    $system_file = new system_file($file);
    $system_dir = new system_file($dir);
    $system_file->chmod();
    $system_dir->chmod();
    clearstatcache();
    $this->assertEquals('0766', substr(sprintf('%o', fileperms($file)), -4));
    $this->assertEquals('0755', substr(sprintf('%o', fileperms($dir)), -4));
    rmdir($dir);
  }

  public function testEmpty_directory()
  {
    $file = __DIR__ . '/../testfiles/cestlafete.jpg';
    $dir = __DIR__ . '/testchmod';
    system_file::mkdir($dir);

    copy($file, $dir . '/v1.test');
    copy($file, $dir . '/v2.test');
    copy($file, $dir . '/v3.test');

    $system_file = new system_file($dir);
    $system_file->empty_directory();

    $this->assertFalse(is_file($dir . '/v1.test'));
    $this->assertFalse(is_file($dir . '/v2.test'));
    $this->assertFalse(is_file($dir . '/v3.test'));

    rmdir($dir);
  }

  public function testSet_phrasea_tech_field()
  {
    $this->objects['wav']->set_phrasea_tech_field('un', '1');
    $this->objects['wav']->set_phrasea_tech_field('trois', '3');
    $this->objects['wav']->set_phrasea_tech_field('deux', '2');

    try
    {
      $this->objects['wav']->set_phrasea_tech_field(' ', '2');
      $this->fail();
    }
    catch (Exception_InvalidArgument $e)
    {

    }

    $this->assertEquals('1', $this->objects['wav']->get_phrasea_tech_field('un'));
    $this->assertEquals('2', $this->objects['wav']->get_phrasea_tech_field('deux'));
    $this->assertEquals('3', $this->objects['wav']->get_phrasea_tech_field('trois'));
  }

  public function testGet_phrasea_tech_field()
  {
    $this->assertNull($this->objects['wav']->get_phrasea_tech_field('qsdfqsdfsqd'));
  }

  public function testExtract_metadatas()
  {
    $appbox = appbox::get_instance(\bootstrap::getCore());
    $databox = null;
    foreach ($appbox->get_databoxes() as $d)
    {
      $databox = $d;
      break;
    }
    $this->assertInstanceOf('databox', $databox);
    $metadatas = $this->objects['wav']->extract_metadatas($databox->get_meta_structure());

    $this->assertTrue(is_array($metadatas));
    $this->assertArrayHasKey('metadatas', $metadatas);
    $this->assertArrayHasKey('status', $metadatas);
    foreach ($metadatas['metadatas'] as $metadata)
    {
      $this->assertTrue(is_array($metadata));
      $this->assertArrayHasKey('meta_struct_id', $metadata);
      $this->assertArrayHasKey('meta_id', $metadata);
      $this->assertArrayHasKey('value', $metadata);
      $this->assertTrue(is_scalar($metadata['value']));
      $this->assertNull($metadata['meta_id']);
      $this->assertTrue(is_int($metadata['meta_struct_id']));
      $this->assertTrue($metadata['meta_struct_id'] > 0);
    }
  }

}
