<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\AST\KeyValue;

use Alchemy\Phrasea\SearchEngine\Elastic\Search\QueryContext;

class GeolocationKey implements Key
{
    const TYPE_GEOLOCATION = 'geolocation';

    private $type;
    private $key;
    private $matcher = '/^(-?\\d*(\\.\\d*)?)[\\s]+(-?\\d*(\\.\\d*)?)[\\s]+(\\d*(\\.\\d*)?)(\\D*)$/';
    private $units = [
        'mi', 'miles',
        'yd', 'yards',
        'ft', 'feet',
        'in', 'inch',
        'km', 'kilometers',
        'm', 'meters',
        'cm', 'centimeters',
        'mm', 'millimeters',
        'NM', 'nmi', 'nauticalmiles',
    ];

    public static function geolocation()
    {
        return new self(self::TYPE_GEOLOCATION, 'geolocation');
    }

    public function buildQuery($value, QueryContext $context)
    {
        $matches = [];
        if(preg_match($this->matcher, trim($value), $matches) === 1) {
            $lat = $matches[1];
            $lon = $matches[3];
            $dst = $matches[5];
            $uni = trim($matches[7]);
            if(in_array($uni, $this->units)) {
                return [
                    'geo_distance' => [
                        'distance' => $dst . $uni,
                        'location' => [
                            'lat' => $lat,
                            'lon' => $lon
                        ]
                    ]
                ];
            }
        }
        return null;
    }


    private function __construct($type, $key)
    {
        $this->type = $type;
        $this->key = $key;
    }

    public function getFieldType(QueryContext $context)
    {
        return $this->type;
    }

    public function getIndexField(QueryContext $context, $raw = false)
    {
        return $this->key;
    }

    public function isValueCompatible($value, QueryContext $context)
    {
        return preg_match($this->matcher, trim($value), $matches) === 1;
    }

    public function __toString()
    {
        return $this->type;
    }
}
