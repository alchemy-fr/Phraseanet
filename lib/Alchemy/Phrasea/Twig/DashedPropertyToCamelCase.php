<?php

namespace Alchemy\Phrasea\Twig;

class DashedPropertyToCamelCase extends \Twig_Extension
{

    /**
     *
     * @return string
     */
    public function getName()
    {
        return 'dashed_property_to_camel_case';
    }

    /**
     *
     * @return array
     */
    public function getFilters()
    {
        return array(
            'dashed_property_to_camel_case' => new \Twig_Filter_Method($this, 'toCamelCase'),
        );
    }

    public function toCamelCase($property)
    {
        $properties = explode('-', $property);

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
