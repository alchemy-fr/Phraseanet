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

class Camelize extends \Twig_Extension
{
    /**
     *
     * @return string
     */
    public function getName()
    {
        return 'camelize';
    }

    /**
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            'camelize' => new \Twig_Filter_Method($this, 'toCamelCase'),
        ];
    }

    public function toCamelCase($str, $separator = '-', $pascalCase = false)
    {
        return StringHelper::camelize($str, $separator, $pascalCase);
    }
}
