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
class DumperTest extends \PHPUnit\Framework\TestCase
{

    public function testDump()
    {
        $b64 = $this->binaryToBase64(__DIR__ . '/smile.png');

        $tests = array(
            "data:image/png;base64," . $b64,
            "data:image/png;paramName=paramValue;base64," . $b64,
            "data:text/plain;charset=utf-8,%23%24%25",
            "data:application/vnd-xxx-query,select_vcount,fcol_from_fieldtable/local"
        );

        //#1
        $dataURI = DataURI\Parser::parse($tests[0]);
        $this->assertEquals($tests[0], DataURI\Dumper::dump($dataURI));

        //#2
        $dataURI = DataURI\Parser::parse($tests[1]);
        $this->assertEquals($tests[1], DataURI\Dumper::dump($dataURI));

        //#3
        $dataURI = DataURI\Parser::parse($tests[2]);
        $this->assertEquals($tests[2], DataURI\Dumper::dump($dataURI));

        //#4
        $dataURI = DataURI\Parser::parse($tests[3]);
        $this->assertEquals($tests[3], rawurldecode(DataURI\Dumper::dump($dataURI)));
    }

    private function binaryToBase64($file)
    {
        return base64_encode(file_get_contents($file));
    }
}
