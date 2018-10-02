<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Application;

class Status implements AttributeInterface
{
    protected $status;

    public function __construct(Application $app, $status)
    {
        /**
         * We store a binary string
         */
        if (is_int($status)) {
            $status = decbin($status);
        } elseif (preg_match('/^[01]+$/', $status)) {
            $status = (string) $status;
        } elseif (ctype_digit($status)) {
            $status = decbin((int) $status);
        } elseif (strpos($status, '0x') === 0 && ctype_xdigit(substr($status, 2))) {
            $status = \databox_status::hex2bin($status);
        } elseif (strpos($status, '0b') === 0 && preg_match('/^[01]+$/', substr($status, 2))) {
            $status = substr($status, 2);
        } elseif (ctype_xdigit($status)) {
            $status = \databox_status::hex2bin($status);
        } else {
            throw new \InvalidArgumentException('Invalid status argument');
        }

        $this->status = $status;
    }

    public function getName()
    {
        return self::NAME_STATUS;
    }

    public function getValue()
    {
        return $this->status;
    }

    public function asString()
    {
        return $this->status;
    }

    public static function loadFromString(Application $app, $string)
    {
        return new static($app, $string);
    }
}
