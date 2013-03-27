<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Twig;

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
        return array(
            'camelize' => new \Twig_Filter_Method($this, 'toCamelCase'),
        );
    }

    public function toCamelCase($property, $separator = '-')
    {
        $properties = explode($separator, $property);

        if(count($properties) > 1) {
            $transformedProperty = "";
            foreach($properties as $chunk) {
                $transformedProperty .= ucfirst($chunk);
            }

            $property = lcfirst($transformedProperty);
        }

        return $property;
    }
}
