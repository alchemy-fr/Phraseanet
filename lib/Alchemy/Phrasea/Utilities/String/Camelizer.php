<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Utilities\String;

class Camelizer
{
    public function camelize($str, $separator = '_', $pascalCase = false)
    {
        $transformStr = str_replace(' ', '', ucwords(str_replace($separator, ' ', $str)));

        return $pascalCase ? $transformStr : lcfirst($transformStr);
    }
}
