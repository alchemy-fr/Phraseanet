<?php

require_once __DIR__ . '/../../PhraseanetPHPUnitAbstract.class.inc';

class Feed_XML_AtomTest extends PhraseanetPHPUnitAbstract
{
    /**
     *
     * @var Feed_XML_Atom
     */
    protected static $atom;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$atom = new Feed_XML_Atom();
    }

    public function testRender()
    {
        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
    }

    public function testSet_author_name()
    {
        self::$atom->set_author_name('boulbil');
        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals('boulbil', (string) $sxe->author->name);

        self::$atom->set_author_name('boubi bouba');
        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals('boubi bouba', (string) $sxe->author->name);
    }

    public function testSet_author_email()
    {
        self::$atom->set_author_email('bouba@example.net');
        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals('bouba@example.net', (string) $sxe->author->email);

        self::$atom->set_author_email('email+test@example.org');
        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals('email+test@example.org', (string) $sxe->author->email);
    }

    public function testSet_author_url()
    {
        self::$atom->set_author_url('http://example.net/');
        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals('http://example.net/', (string) $sxe->author->uri);
    }

    public function testSet_title()
    {
        $title = 'Un joli titre';
        self::$atom->set_title($title);
        self::$atom->set_title($title);

        $xml = self::$atom->render();
        $sxe = simplexml_load_string($xml);
        $namespaces = $sxe->getDocNamespaces();
        $sxe->registerXPathNamespace('__empty_ns', $namespaces['']);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $obj = $sxe->xpath('/__empty_ns:feed/__empty_ns:title');

        $this->assertEquals(1, count($obj));

        $this->assertEquals($title, (string) $obj[0]);
    }

    public function testSet_updated_on()
    {
        $date_obj = new DateTime();
        self::$atom->set_updated_on($date_obj);

        $sxe = simplexml_load_string(self::$atom->render());
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $this->assertEquals($date_obj->format(DATE_ATOM), (string) $sxe->updated);
    }

    public function testSet_subtitle()
    {
        $subtitle = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
        self::$atom->set_subtitle($subtitle);
        self::$atom->set_subtitle($subtitle);

        $xml = self::$atom->render();
        $sxe = simplexml_load_string($xml);
        $namespaces = $sxe->getDocNamespaces();
        $sxe->registerXPathNamespace('__empty_ns', $namespaces['']);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $obj = $sxe->xpath('/__empty_ns:feed/__empty_ns:subtitle');

        $this->assertEquals(1, count($obj));

        $this->assertEquals($subtitle, (string) $obj[0]);
    }

    public function testSet_link()
    {
        $href = 'http://www.example.org/?page=24';
        $current_page = new Feed_Link($href, 'example next page', 'application/atom+xml');
        self::$atom->set_link($current_page);
        self::$atom->set_link($current_page);

        $xml = self::$atom->render();
        $sxe = simplexml_load_string($xml);
        $namespaces = $sxe->getDocNamespaces();
        $sxe->registerXPathNamespace('__empty_ns', $namespaces['']);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $obj = $sxe->xpath('/__empty_ns:feed/__empty_ns:link[@rel="self"]');

        $this->assertEquals(1, count($obj));
        $found = false;

        foreach ($obj[0]->attributes() as $attr => $attr_value) {
            if ($attr != 'href')
                continue;
            $found = true;
            $this->assertEquals($href, (string) $attr_value);
        }

        if ( ! $found)
            $this->fail();
    }

    public function testSet_next_page()
    {
        $href = 'http://www.example.org/?page=23';
        $next_page = new Feed_Link($href, 'example next page', 'application/atom+xml');
        self::$atom->set_next_page($next_page);
        self::$atom->set_next_page($next_page);

        $xml = self::$atom->render();
        $sxe = simplexml_load_string($xml);
        $namespaces = $sxe->getDocNamespaces();
        $sxe->registerXPathNamespace('__empty_ns', $namespaces['']);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $obj = $sxe->xpath('/__empty_ns:feed/__empty_ns:link[@rel="next"]');

        $this->assertEquals(1, count($obj));
        $found = false;

        foreach ($obj[0]->attributes() as $attr => $attr_value) {
            if ($attr != 'href')
                continue;
            $found = true;
            $this->assertEquals($href, (string) $attr_value);
        }

        if ( ! $found)
            $this->fail();
    }

    public function testSet_previous_page()
    {
        $href = 'http://www.example.org/?page=25';
        $prev_page = new Feed_Link($href, 'example next page', 'application/atom+xml');
        self::$atom->set_previous_page($prev_page);
        self::$atom->set_previous_page($prev_page);

        $xml = self::$atom->render();
        $sxe = simplexml_load_string($xml);
        $namespaces = $sxe->getDocNamespaces();
        $sxe->registerXPathNamespace('__empty_ns', $namespaces['']);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $obj = $sxe->xpath('/__empty_ns:feed/__empty_ns:link[@rel="previous"]');

        $this->assertEquals(1, count($obj));
        $found = false;

        foreach ($obj[0]->attributes() as $attr => $attr_value) {
            if ($attr != 'href')
                continue;
            $found = true;
            $this->assertEquals($href, (string) $attr_value);
        }

        if ( ! $found)
            $this->fail();
    }

    public function testSet_generator()
    {
        $generator = 'Arnold Schwarzenegger';
        self::$atom->set_generator($generator);
        self::$atom->set_generator($generator);

        $xml = self::$atom->render();
        $sxe = simplexml_load_string($xml);
        $namespaces = $sxe->getDocNamespaces();
        $sxe->registerXPathNamespace('__empty_ns', $namespaces['']);
        $this->assertInstanceOf('SimpleXMLElement', $sxe);
        $obj = $sxe->xpath('/__empty_ns:feed/__empty_ns:generator');

        $this->assertEquals(1, count($obj));

        $this->assertEquals($generator, (string) $obj[0]);
    }

    public function testGet_mimetype()
    {
        $this->assertEquals('application/atom+xml', self::$atom->get_mimetype());
    }
}
