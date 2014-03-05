<?php

class p4stringTest extends \PhraseanetTestCase
{
    public function testAddEndSlash()
    {
        $string = '';
        $this->assertEquals(getcwd() . '/', p4string::addEndSlash($string));
        $string = '/';
        $this->assertEquals('/', p4string::addEndSlash($string));
        $string = '//';
        $this->assertEquals('//', p4string::addEndSlash($string));
        $string = '\\';
        $this->assertEquals('\\', p4string::addEndSlash($string));
        $string = '\\\\';
        $this->assertEquals('\\\\', p4string::addEndSlash($string));
        $string = '/alalal/';
        $this->assertEquals('/alalal/', p4string::addEndSlash($string));
    }

    public function testJSstring()
    {
        $this->assertEquals('babébibobu & marcel \'\"', p4string::JSstring('babébibobu & marcel \'"'));
    }

    public function testMakeString()
    {
        /**
         * @deprecated
         */
    }

    public function testHasAccent()
    {
        $this->assertTrue(p4string::hasAccent('azertyuéjn'));
        $this->assertFalse(p4string::hasAccent('azertyujn'));
        $this->assertFalse(p4string::hasAccent(''));
        $this->assertTrue(p4string::hasAccent('é'));
    }

    public function testJsonencode()
    {
        $a = new stdClass();
        $a->prout = 'pue';
        $a->couleur = ['marron', 'jaune'];
        $a->compteur = ['incrémental' => true, 'fiente'      => 'vrai'];
        $this->assertEquals('{"prout":"pue","couleur":["marron","jaune"],"compteur":{"incr\u00e9mental":true,"fiente":"vrai"}}', p4string::jsonencode($a));

        $b = ['un', 'petit' => 'tout petit', 'cul', 1       => 'qui', 10      => 'roule'];
        $this->assertEquals('{"0":"un","petit":"tout petit","1":"qui","10":"roule"}', p4string::jsonencode($b));

        $c = ['gros', 'chien'];
        $this->assertEquals('["gros","chien"]', p4string::jsonencode($c));
    }

    public function testFormat_octets()
    {
        $size = 1024;
        $this->assertEquals('1 ko', p4string::format_octets($size));
        $size = 824;
        $this->assertEquals('824 o', p4string::format_octets($size));
        $size = 102;
        $this->assertEquals('102 o', p4string::format_octets($size));
        $size = 10245335;
        $this->assertRegExp('/^9[,\.]{1}77 Mo$/', p4string::format_octets($size));
        $size = 10245335;
        $this->assertRegExp('/^9[,\.]{1}771 Mo$/', p4string::format_octets($size, 3));
        $this->assertEquals('10 Mo', p4string::format_octets($size, 0));
        $size = 9990245335123153;
        $this->assertRegexp('/^9086[,\.]{1}08 To$/', p4string::format_octets($size));
        $size = 2048;
        $this->assertEquals('2 ko', p4string::format_octets($size));
    }

    public function testFormat_seconds()
    {
        $this->assertEquals('07:38', p4string::format_seconds(458));
        $this->assertEquals('15:46:31', p4string::format_seconds(56791));
        $this->assertEquals('2737:59:51', p4string::format_seconds(9856791));
        $this->assertEquals('00:00', p4string::format_seconds(0));
        $this->assertEquals('', p4string::format_seconds(-15));
    }
}
