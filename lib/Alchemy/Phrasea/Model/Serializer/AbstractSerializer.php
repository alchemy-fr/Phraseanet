<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Serializer;

abstract class AbstractSerializer
{
    protected function sanitizeSerializedValue($value)
    {
        return str_replace([
            "\x00", //null
            "\x01", //start heading
            "\x02", //start text
            "\x03", //end of text
            "\x04", //end of transmission
            "\x05", //enquiry
            "\x06", //acknowledge
            "\x07", //bell
            "\x08", //backspace
            "\x0C", //new page
            "\x0E", //shift out
            "\x0F", //shift in
            "\x10", //data link escape
            "\x11", //dc 1
            "\x12", //dc 2
            "\x13", //dc 3
            "\x14", //dc 4
            "\x15", //negative ack
            "\x16", //synchronous idle
            "\x17", //end of trans block
            "\x18", //cancel
            "\x19", //end of medium
            "\x1A", //substitute
            "\x1B", //escape
            "\x1C", //file separator
            "\x1D", //group sep
            "\x1E", //record sep
            "\x1F", //unit sep
        ], '', $value);
    }
}
