<?php

namespace MediaVorus;

use MediaVorus\Exception\FileNotFoundException;

class FileTest extends TestCase
{

    public function testGetMimeType()
    {
        $object = new File(__DIR__ . '/../../files/CanonRaw.cr2');
        $this->assertEquals('image/x-tika-canon', $object->getMimeType());
    }

    public function testGetMimeTypeApe()
    {
        $object = new File(__DIR__ . '/../../files/APE.ape');
        $this->assertEquals('audio/x-monkeys-audio', $object->getMimeType());
    }

    public function testFileNotFound()
    {
        $this->expectException(FileNotFoundException::class);
        new File(__DIR__ . '/../../files/nonExistentFile');
    }
}
