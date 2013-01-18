<?php

class thesaurusTest extends PhraseanetPHPUnitAbstract
{

    public function testXquery_escape()
    {
        $string = 'Eléphant ';
        $this->assertEquals($string, thesaurus::xquery_escape($string));
        $string = '&é"\'(-è_ çà)=ù*!:;,?./§%µ+°0987654321';
        $this->assertEquals('&amp;é&quot;&apos;(-è_ çà)=ù*!:;,?./§%µ+°0987654321', thesaurus::xquery_escape($string));
    }
}
