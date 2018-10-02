<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile\Symlink;

class SymLinkerEncoder
{
    protected $key;

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function encode($pathFile)
    {
        return hash_hmac('sha512', $pathFile , $this->key);
    }
}
