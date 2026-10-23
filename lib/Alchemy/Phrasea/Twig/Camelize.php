<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Twig;

use Alchemy\Phrasea\Utilities\StringHelper;

class Camelize extends \Twig\Extension\AbstractExtension
{
    /**
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('camelize', [$this, 'toCamelCase']),
        ];
    }

    public function toCamelCase($str, $separator = '-', $pascalCase = false)
    {
        return StringHelper::camelize($str, $separator, $pascalCase);
    }
}
