<?php

class set_exportTest extends PhraseanetPHPUnitAuthenticatedAbstract
{
    public function testStreamFileExportName()
    {
        $response = \set_export::stream_file(self::$DI['app'], __DIR__ . '/../../files/HelloWorld.pdf', 'to1\/\àçÂto.jpg', 'application/pdf');
        $explode = explode(';', $response->headers->get('content-disposition'));

        $filenameData = explode('=', $explode[1]);
        $fallbackData = explode('\'\'', $explode[2]);

        $filename =  array_pop($filenameData);
        $filenameFallback = array_pop($fallbackData);

        $this->assertEquals('"to1acato.jpg"', $filename);
        $this->assertEquals('to1àçÂto.jpg', rawurldecode($filenameFallback));
    }

    public function testStreamFilenotFound()
    {
        $response = \set_export::stream_file(self::$DI['app'], __DIR__ . '/../../files/Unknown.pdf', 'to1\/\\o.jpg', 'application/pdf');

        $this->assertEquals(404, $response->getStatusCode());
    }
}
