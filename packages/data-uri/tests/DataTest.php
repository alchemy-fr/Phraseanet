<?php

/**
 * Copyright (c) 2012 Alchemy-fr
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 * IN THE SOFTWARE.
 */

/**
 *
 * @author      Nicolas Le Goff
 * @author      Phraseanet team
 * @license     http://opensource.org/licenses/MIT MIT
 */
class DataTest extends \PHPUnit\Framework\TestCase
{

    public function testTooLongException()
    {
        $i = 0;
        $string = '';
        while ($i < DataURI\Data::ATTS_TAG_LIMIT + 1) {
            $string .= 'x';
            $i ++;
        }

        try {
            $dataURI = new DataURI\Data($string, null, array(), true);
            $this->fail('An exception should have beeen raised');
        } catch (DataURI\Exception\TooLongDataException $e) {
            $this->assertEquals(DataURI\Data::ATTS_TAG_LIMIT + 1, $e->getLength());
        }

        $dataURI = new DataURI\Data($string);

        try {
            $dataURI = new DataURI\Data($string, null, array(), true, DataURI\Data::LITLEN);
            $this->fail('An exception should have beeen raised');
        } catch (DataURI\Exception\TooLongDataException $e) {

        }
    }

    public function testGetData()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new DataURI\Data($dataString);
        $this->assertEquals($dataString, $dataURI->getData());
    }

    public function testGetMimeType()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $mimeType = 'text/plain';
        $dataURI = new DataURI\Data($dataString, $mimeType);
        $this->assertEquals($mimeType, $dataURI->getMimeType());
    }

    public function testGetParameters()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $mimeType = 'text/plain';
        $parameters = array('charset', 'utf-8');
        $dataURI = new DataURI\Data($dataString, $mimeType, $parameters);
        $this->assertEquals($parameters, $dataURI->getParameters());
        $this->assertTrue(is_array($dataURI->getParameters()));
    }

    public function testIsBinaryData()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new DataURI\Data($dataString);
        $dataURI->setBinaryData(true);
        $this->assertTrue($dataURI->isBinaryData());
    }

    public function testInit()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new DataURI\Data($dataString);
        $parameters = $dataURI->getParameters();
        $this->assertTrue(array_key_exists('charset', $parameters));
        $this->assertEquals('US-ASCII', $parameters['charset']);

        $this->assertEquals('text/plain', $dataURI->getMimeType());
    }

    public function testAddParameters()
    {
        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new DataURI\Data($dataString);
        $current = count($dataURI->getParameters());
        $dataURI->addParameters('charset', 'iso-8859-7');
        $this->assertEquals($current, count($dataURI->getParameters()));
        $dataURI->addParameters('another-charset', 'iso-8859-7');
        $this->assertGreaterThan($current, count($dataURI->getParameters()));
        $this->assertTrue(array_key_exists('another-charset', $dataURI->getParameters()));
    }

    public function testBuildFromFile()
    {
        $file = __DIR__ . '/smile.png';
        $dataURI = DataURI\Data::buildFromFile($file);
        $this->assertInstanceOf('DataURI\Data', $dataURI);
        $this->assertEquals('image/png', $dataURI->getMimeType());
        $this->assertEquals(file_get_contents($file), $dataURI->getData());
    }

    public function testFileNotFound()
    {
        $this->expectException(\DataURI\Exception\FileNotFoundException::class);

        $filename = __DIR__ . '/unknown-file';

        $dataString = 'Lorem ipsum dolor sit amet';
        $dataURI = new DataURI\Data($dataString);
        $dataURI->write($filename);
    }

    public function testFileNotFoundFromFile()
    {
            $this->expectException(\DataURI\Exception\FileNotFoundException::class);

        $filename = __DIR__ . '/unknown-file';

        DataURI\Data::buildFromFile($filename);
    }

    public function testWrite()
    {
        $filename = __DIR__ . '/test';
        $this->createEmptyFile($filename);
        $dataString = 'hello world';
        $dataURI = new DataURI\Data($dataString);
        $dataURI = DataURI\Data::buildFromFile($dataURI->write($filename));
        $this->assertEquals($dataString, $dataURI->getData());
        unlink($filename);
    }

    private function createEmptyFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }

        $handle = fopen($filename, 'x+');
        fwrite($handle, '');
        fclose($handle);
    }
}
