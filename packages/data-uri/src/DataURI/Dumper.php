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

namespace DataURI;

use DataURI\Data;

/**
 *
 * @author      Nicolas Le Goff
 * @author      Phraseanet team
 * @license     http://opensource.org/licenses/MIT MIT
 */
class Dumper
{

    /**
     * Transform a DataURI\Data object to its URI representation and take
     * the following form :
     *
     * data:[<mediatype>][;base64],<data>
     *
     * @param Data $dataURI
     * @return string
     */
    public static function dump(Data $dataURI)
    {
        $parameters = '';

        if (0 !== count($params = $dataURI->getParameters())) {
            foreach ($params as $paramName => $paramValue) {
                $parameters .= sprintf(';%s=%s', $paramName, $paramValue);
            }
        }

        $base64 = '';

        if($dataURI->isBinaryData()){
            $base64 = sprintf(';%s', Data::BASE_64);
            $data = base64_encode($dataURI->getData());
        }else{
            $data = rawurlencode($dataURI->getData());
        }

        return sprintf('data:%s%s%s,%s'
                , $dataURI->getMimeType()
                , $parameters
                , $base64
                , $data
        );
    }
}
