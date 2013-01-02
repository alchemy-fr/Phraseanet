<?php

require_once __DIR__ . '/../PhraseanetPHPUnitAuthenticatedAbstract.class.inc';

class exportTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    public function testStreamFileExportName()
    {
        $response = \set_export::stream_file(__DIR__ . '/../testfiles/HelloWorld.pdf', 'to1\/\àçÂto.jpg', 'application/pdf');
        $explode = explode(';', $response->headers->get('content-disposition'));
        
        $filename =  array_pop(explode('=', $explode[1]));
        $filenameFallback = array_pop(explode('\'\'', $explode[2]));
        
        $this->assertEquals('"to1acato.jpg"', $filename);
        $this->assertEquals('to1àçÂto.jpg', rawurldecode($filenameFallback));
    }
    
    public function testStreamFilenotFound()
    {
        $response = \set_export::stream_file(__DIR__ . '/../testfiles/Unknown.pdf', 'to1\/\\o.jpg', 'application/pdf');
        
        $this->assertEquals(404, $response->getStatusCode());
    }
}
