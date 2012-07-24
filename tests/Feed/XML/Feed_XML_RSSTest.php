<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class Feed_XML_RSSTest extends PhraseanetPHPUnitAbstract
{
    /**
     * @var Feed_XML_RSS
     */
    protected static $rss;

    public function setUp()
    {
        parent::setUp();
        self::$rss = new Feed_XML_RSS();
    }

    public function testSet_language()
    {
        $language = 'fr-fr';
        self::$rss->set_language($language);

        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals($language, (string) $sxe->channel->language);
    }

    public function testSet_copyright()
    {
        $copyright = '2006-2014 No copyright';
        self::$rss->set_copyright($copyright);

        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals($copyright, (string) $sxe->channel->copyright);
    }

    public function testSet_managingEditor()
    {
        $email = 'manager@example.org';
        self::$rss->set_managingEditor($email);

        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals($email, (string) $sxe->channel->managingEditor);
    }

    public function testSet_webMaster()
    {
        $email = 'webmaster@example.org';
        self::$rss->set_webMaster($email);

        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals($email, (string) $sxe->channel->webMaster);
    }

    public function testSet_lastBuildDate()
    {
        $last_build = new DateTime('-2 hours');
        self::$rss->set_lastBuildDate($last_build);


        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals($last_build->format(DATE_RFC2822), (string) $sxe->channel->lastBuildDate);
    }

    public function testSet_category()
    {
        $categories = array('banana' => 'banana', 'prout'  => 'prout');
        foreach ($categories as $category) {
            self::$rss->set_category($category);
        }

        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        $cat_objs = $sxe->xpath('//channel/category');
        $this->assertEquals(count($cat_objs), count($categories));

        foreach ($cat_objs as $cat_obj) {
            $str_cat = (string) $cat_obj;
            $this->assertArrayHasKey($str_cat, $categories);
            unset($categories[$str_cat]);
        }

        $this->assertTrue((count($categories) === 0));
    }

    public function testSet_docs()
    {
        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        $this->assertEquals('http://blogs.law.harvard.edu/tech/rss', (string) $sxe->channel->docs);

        self::$rss->set_docs('http://www.example.org');
        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        $this->assertEquals('http://www.example.org', (string) $sxe->channel->docs);
    }

    public function testSet_ttl()
    {
        self::$rss->set_ttl(240);
        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        $this->assertEquals(240, (string) $sxe->channel->ttl);
    }

    public function testSet_image()
    {
        $link = 'http://www.example.org';
        $title = 'Un beau titre';
        $url = 'http://www.example.org/image.jpg';
        $image = new Feed_XML_RSS_Image($url, $title, $link);
        $width = 42;
        $height = 30;
        $description = 'KIKOO';
        $image->set_width($width);
        $image->set_height($height);
        $image->set_description($description);


        self::$rss->set_image($image);
        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        $this->assertEquals($title, (string) $sxe->channel->image->title);
        $this->assertEquals($link, (string) $sxe->channel->image->link);
        $this->assertEquals($url, (string) $sxe->channel->image->url);
        $this->assertEquals($height, (string) $sxe->channel->image->height);
        $this->assertEquals($width, (string) $sxe->channel->image->width);
        $this->assertEquals($description, (string) $sxe->channel->image->description);
    }

    public function testSet_skipHours()
    {
        $hours = array('4'  => '4', '8'  => '8', '12' => '12');
        foreach ($hours as $hour) {
            self::$rss->set_skipHour($hour);
        }
        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        foreach ($sxe->channel->skipHours->hour as $sx_hour) {
            $sx_hour = (string) $sx_hour;
            $this->assertArrayHasKey($sx_hour, $hours);
            unset($hours[$sx_hour]);
        }

        $this->assertTrue(empty($hours));
    }

    public function testSet_skipDays()
    {
        $days = array('sunday'   => 'sunday', 'saturday' => 'saturday', 'monday'   => 'monday');
        foreach ($days as $day) {
            self::$rss->set_skipDays($day);
        }
        $xml = self::$rss->render();
        $sxe = simplexml_load_string($xml);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);

        foreach ($sxe->channel->skipDays->day as $sx_day) {
            $sx_day = (string) $sx_day;
            $this->assertArrayHasKey($sx_day, $days);
            unset($days[$sx_day]);
        }

        $this->assertTrue(empty($days));
    }

    public function testRender()
    {
        $sxe = simplexml_load_string(self::$rss->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
    }
}
