<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Twig;

use Alchemy\Phrasea\Utilities\String\Camelizer;

class Camelize extends \Twig_Extension
{
    /**
     * @var Camelizer
     */
    private $camelizer;

    public function __construct(Camelizer $camelizer = null)
    {
        $this->camelizer = $camelizer ?: new Camelizer();
    }

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
        return array(
            'camelize' => new \Twig_Filter_Method($this, 'toCamelCase'),
        );
    }

    public function toCamelCase($str, $separator = '-', $pascalCase = false)
    {
        return $this->camelizer->camelize($str, $separator, $pascalCase);
    }
}
