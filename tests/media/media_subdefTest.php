<?php

use Alchemy\Phrasea\Border\File;

require_once __DIR__ . '/../PhraseanetPHPUnitAbstract.class.inc';

class media_subdefTest extends \PhraseanetPHPUnitAbstract
{
    /**
     * @var \media_subdef
     */
    protected static $objectPresent;
    /**
     * @var \media_subdef
     */
    protected static $storyPresent;

    /**
     * @var \media_subdef
     */
    protected static $objectNotPresent;

    /**
     * @var \record_adapter
     */
    protected static $recordonbleu;

    /**
     * @covers media_subdef::__construct
     * @covers media_subdef::create
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $file = new File(self::$DI['app'], self::$DI['app']['mediavorus']->guess(__DIR__ . "/../testfiles/iphone_pic.jpg"), self::$DI['collection']);

        self::$recordonbleu = record_adapter::createFromFile($file, self::$DI['app']);
        self::$recordonbleu->generate_subdefs(self::$recordonbleu->get_databox(), self::$DI['app']);

        foreach (self::$recordonbleu->get_subdefs() as $subdef) {

            if ($subdef->get_name() == 'document') {
                continue;
            }

            if ( ! self::$objectPresent) {
                self::$objectPresent = $subdef;
                continue;
            }
            if ( ! self::$objectNotPresent) {
                self::$objectNotPresent = $subdef;
                continue;
            }
        }

        $story = \record_adapter::createStory(self::$DI['app'], self::$DI['collection']);

        self::$objectNotPresent->remove_file();
        self::$storyPresent = $story->get_subdef('thumbnail');
    }

    /**
     * @covers media_subdef::is_physically_present
     */
    public function testIs_physically_present()
    {
        $this->assertTrue(self::$objectPresent->is_physically_present());
        $this->assertFalse(self::$objectNotPresent->is_physically_present());
    }

    /**
     * @covers media_subdef::is_physically_present
     */
    public function testStoryIsNotPhysicallyPresent()
    {
        $this->assertFalse(self::$storyPresent->is_physically_present());
    }

    /**
     * @covers media_subdef::get_record
     */
    public function testGet_record()
    {
        $this->assertEquals(self::$recordonbleu->get_record_id(), self::$objectNotPresent->get_record()->get_record_id());
        $this->assertEquals(self::$recordonbleu->get_record_id(), self::$objectPresent->get_record()->get_record_id());
        $this->assertEquals(self::$recordonbleu->get_sbas_id(), self::$objectNotPresent->get_record()->get_sbas_id());
        $this->assertEquals(self::$recordonbleu->get_sbas_id(), self::$objectPresent->get_record()->get_sbas_id());
    }

    /**
     * @covers media_subdef::get_permalink
     */
    public function testGet_permalink()
    {
        $this->assertInstanceOf('\\media_Permalink_adapter', self::$objectNotPresent->get_permalink());
        $this->assertInstanceOf('\\media_Permalink_adapter', self::$objectPresent->get_permalink());
    }

    /**
     * @covers media_subdef::get_record_id
     */
    public function testGet_record_id()
    {
        $this->assertEquals(self::$recordonbleu->get_record_id(), self::$objectNotPresent->get_record()->get_record_id());
        $this->assertEquals(self::$recordonbleu->get_record_id(), self::$objectPresent->get_record()->get_record_id());
    }

    /**
     * @covers media_subdef::getEtag
     */
    public function testGetEtag()
    {
        $this->assertNull(self::$objectNotPresent->getEtag());
        $this->assertInternalType('string', self::$objectPresent->getEtag());
        $this->assertRegExp('/[a-zA-Z0-9]{32}/', self::$objectPresent->getEtag());
    }

    /**
     * @covers media_subdef::setEtag
     */
    public function testSetEtag()
    {
        $etag = md5('random');
        self::$objectNotPresent->setEtag($etag);
        $this->assertEquals($etag, self::$objectNotPresent->getEtag());
    }

    /**
     * @covers media_subdef::get_sbas_id
     */
    public function testGet_sbas_id()
    {
        $this->assertEquals(self::$recordonbleu->get_sbas_id(), self::$objectNotPresent->get_record()->get_sbas_id());
        $this->assertEquals(self::$recordonbleu->get_sbas_id(), self::$objectPresent->get_record()->get_sbas_id());
    }

    /**
     * @covers media_subdef::get_type
     */
    public function testGet_type()
    {
        $this->assertEquals(\media_subdef::TYPE_IMAGE, self::$objectPresent->get_type());
    }

    /**
     * @covers media_subdef::get_mime
     */
    public function testGet_mime()
    {
        $this->assertEquals('image/jpeg', self::$objectPresent->get_mime());
        $this->assertEquals('image/png', self::$objectNotPresent->get_mime());
    }

    /**
     * @covers media_subdef::get_path
     */
    public function testGet_path()
    {
        $this->assertEquals(dirname(self::$objectPresent->get_pathfile()) . DIRECTORY_SEPARATOR, self::$objectPresent->get_path());
        $this->assertEquals(dirname(self::$objectNotPresent->get_pathfile()) . DIRECTORY_SEPARATOR, self::$objectNotPresent->get_path());
    }

    /**
     * @covers media_subdef::get_file
     */
    public function testGet_file()
    {
        $this->assertEquals(basename(self::$objectPresent->get_pathfile()), self::$objectPresent->get_file());
        $this->assertEquals(basename(self::$objectNotPresent->get_pathfile()), self::$objectNotPresent->get_file());
    }

    /**
     * @covers media_subdef::get_size
     */
    public function testGet_size()
    {
        $this->assertTrue(self::$objectPresent->get_size() > 0);
        $this->assertTrue(self::$objectNotPresent->get_size() > 0);
    }

    /**
     * @covers media_subdef::get_name
     */
    public function testGet_name()
    {
        $this->assertTrue(in_array(self::$objectPresent->get_name(), array('thumbnail', 'preview')));
        $this->assertTrue(in_array(self::$objectNotPresent->get_name(), array('thumbnail', 'preview')));
    }

    /**
     * @covers media_subdef::get_subdef_id
     */
    public function testGet_subdef_id()
    {
        $this->assertInternalType('int', self::$objectPresent->get_subdef_id());
        $this->assertInternalType('int', self::$objectNotPresent->get_subdef_id());
        $this->assertTrue(self::$objectPresent->get_size() > 0);
        $this->assertTrue(self::$objectNotPresent->get_size() > 0);
    }

    /**
     * @covers media_subdef::is_substituted
     */
    public function testIs_substituted()
    {
        $this->assertFalse(self::$objectPresent->is_substituted());
        $this->assertFalse(self::$objectNotPresent->is_substituted());
    }

    /**
     * @covers media_subdef::get_pathfile
     */
    public function testGet_pathfile()
    {
        $this->assertEquals(self::$objectPresent->get_path() . self::$objectPresent->get_file(), self::$objectPresent->get_pathfile());
        $this->assertEquals(self::$objectNotPresent->get_path() . self::$objectNotPresent->get_file(), self::$objectNotPresent->get_pathfile());
        $this->assertTrue(file_exists(self::$objectPresent->get_pathfile()));
        $this->assertTrue(file_exists(self::$objectNotPresent->get_pathfile()));
        $this->assertTrue(is_readable(self::$objectPresent->get_pathfile()));
        $this->assertTrue(is_readable(self::$objectNotPresent->get_pathfile()));
        $this->assertTrue(is_writable(self::$objectPresent->get_pathfile()));
        $this->assertTrue(is_writable(self::$objectNotPresent->get_pathfile()));
    }

    /**
     * @covers media_subdef::get_modification_date
     */
    public function testGet_modification_date()
    {
        $this->assertInstanceOf('\\DateTime', self::$objectPresent->get_modification_date());
        $this->assertInstanceOf('\\DateTime', self::$objectNotPresent->get_modification_date());
    }

    /**
     * @covers media_subdef::get_creation_date
     */
    public function testGet_creation_date()
    {
        $this->assertInstanceOf('\\DateTime', self::$objectPresent->get_creation_date());
        $this->assertInstanceOf('\\DateTime', self::$objectNotPresent->get_creation_date());
    }

    /**
     * @covers media_subdef::renew_url
     */
    public function testRenew_url()
    {
        $this->assertInternalType('string', self::$objectPresent->renew_url());
        $this->assertInternalType('string', self::$objectNotPresent->renew_url());
    }

    /**
     * @covers media_subdef::getDataboxSubdef
     */
    public function testGetDataboxSubdef()
    {
        $this->assertInstanceOf('\\databox_subdef', self::$objectPresent->getDataboxSubdef());
        $this->assertInstanceOf('\\databox_subdef', self::$objectNotPresent->getDataboxSubdef());
    }

    /**
     * @covers media_subdef::rotate
     */
    public function testRotate()
    {
        $width_before = self::$objectPresent->get_width();
        $height_before = self::$objectPresent->get_height();

        self::$objectPresent->rotate(90, self::$DI['app']['media-alchemyst'], self::$DI['app']['mediavorus']);

        $this->assertEquals($width_before, self::$objectPresent->get_height());
        $this->assertEquals($height_before, self::$objectPresent->get_width());
    }

    /**
     * @covers media_subdef::rotate
     * @expectedException \Alchemy\Phrasea\Exception\RuntimeException
     * @covers \Alchemy\Phrasea\Exception\RuntimeException
     */
    public function testRotateOnSubstitution()
    {
        self::$objectNotPresent->rotate(90, self::$DI['app']['media-alchemyst'], self::$DI['app']['mediavorus']);
    }

    /**
     * @covers media_subdef::readTechnicalDatas
     */
    public function testReadTechnicalDatas()
    {
        $technical_datas = self::$objectPresent->readTechnicalDatas(self::$DI['app']['mediavorus']);
        $this->assertArrayHasKey(media_subdef::TC_DATA_WIDTH, $technical_datas);
        $this->assertArrayHasKey(media_subdef::TC_DATA_HEIGHT, $technical_datas);
        $this->assertArrayHasKey(media_subdef::TC_DATA_CHANNELS, $technical_datas);
        $this->assertArrayHasKey(media_subdef::TC_DATA_COLORDEPTH, $technical_datas);
        $this->assertArrayHasKey(media_subdef::TC_DATA_MIMETYPE, $technical_datas);
        $this->assertArrayHasKey(media_subdef::TC_DATA_FILESIZE, $technical_datas);

        $technical_datas = self::$objectNotPresent->readTechnicalDatas(self::$DI['app']['mediavorus']);
        $this->assertEquals(array(), $technical_datas);
    }
}
