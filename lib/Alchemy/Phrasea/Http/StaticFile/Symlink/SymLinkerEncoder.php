<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Http\StaticFile\Symlink;

use Silex\Application;
use Symfony\Component\Filesystem\Filesystem;
use Guzzle\Http\Url;

class SymLinkerEncoder
{
    protected $key;

    public static function create(Application $app)
    {
        return new self(
            $app['phraseanet.configuration']['main']['key']
        );
    }

    public function __construct($key)
    {
        $this->key = $key;
    }

    public function encode($pathFile)
    {
        return hash_hmac('sha512', $pathFile , $this->key);
    }
}
