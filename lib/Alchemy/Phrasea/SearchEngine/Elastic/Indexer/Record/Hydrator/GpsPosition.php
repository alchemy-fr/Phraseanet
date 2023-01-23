<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Hydrator;

use InvalidArgumentException;

class GpsPosition
{
    const FULL_GEO_NOTATION = 'FullNotation';
    const LONGITUDE_TAG_NAME = 'Longitude';
    const LONGITUDE_REF_TAG_NAME = 'LongitudeRef';
    const LONGITUDE_REF_WEST = 'W';
    const LONGITUDE_REF_EAST = 'E';
    const LATITUDE_TAG_NAME = 'Latitude';
    const LATITUDE_REF_TAG_NAME = 'LatitudeRef';
    const LATITUDE_REF_NORTH = 'N';
    const LATITUDE_REF_SOUTH = 'S';

    private $longitude;
    private $longitude_ref;
    private $latitude;
    private $latitude_ref;

    public function __construct()
    {
        $this->clear();
    }

    public function clear()
    {
        $this->longitude = $this->longitude_ref = $this->latitude = $this->latitude_ref = null;
    }

    public function set($tag_name, $value)
    {
        switch ($tag_name) {
            case self::LONGITUDE_TAG_NAME:
                if(is_numeric($value)) {
                    $value = (float)$value;
                    if ($value >= -180.0 && $value <= 180.0) {
                        $this->longitude = $value;
                    }
                }
                break;

            case self::LATITUDE_TAG_NAME:
                if(is_numeric($value)) {
                    $value = (float)$value;
                    if ($value >= -90.0 && $value <= 90.0) {
                        $this->latitude = $value;
                    }
                }
                break;

            case self::LONGITUDE_REF_TAG_NAME:
                $normalized = strtoupper($value);
                if ($normalized === self::LONGITUDE_REF_EAST || $normalized === self::LONGITUDE_REF_WEST) {
                    $this->longitude_ref = $value;
                }
                break;

            case self::LATITUDE_REF_TAG_NAME:
                $normalized = strtoupper($value);
                if ($normalized === self::LATITUDE_REF_NORTH || $normalized === self::LATITUDE_REF_SOUTH) {
                    $this->latitude_ref = $normalized;
                }
                break;

            case self::FULL_GEO_NOTATION:
                $re = '/(-?\d+(?:\.\d+)?°?)\s*(\d+(?:\.\d+)?\')?\s*(\d+(?:\.\d+)?")?\s*(N|S|E|W)?/um';
                $normalized = trim(strtoupper($value));
                $matches = null;
                preg_match_all($re, $normalized, $matches, PREG_SET_ORDER, 0);
                if(count($matches) === 2) {     // we need lat and lon
                    $lat = $lon = null;
                    foreach ($matches as $imatch => $match) {
                        if(count($match) != 5) {
                            continue;
                        }
                        $v = 0.0;
                        for($part=1, $div=1.0; $part<=3; $part++, $div*=60.0) {
                            $v += floatval($match[$part]) / $div;
                        }
                        switch($match[4]) {     // N S E W
                            case 'N':
                                $lat = $v;
                                break;
                            case 'S':
                                $lat = -$v;
                                break;
                            case 'E':
                                $lon = $v;
                                break;
                            case 'W':
                                $lon = -$v;
                                break;
                            case '':        // no ref -> lat lon (first=lat, second=lon)
                                if($imatch === 0) {
                                    $lat = $v;
                                }
                                else {
                                    $lon = $v;
                                }
                                break;
                        }
                    }
                    if($lat !== null && $lon != null) {
                        $this->set(self::LATITUDE_TAG_NAME, $lat);
                        $this->set(self::LONGITUDE_TAG_NAME, $lon);
                    }
                }
                break;

            default:
                throw new InvalidArgumentException(sprintf('Unsupported tag name "%s".', $tag_name));
        }
    }

    public static function isSupportedTagName($tag_name)
    {
        return in_array($tag_name, [
            self::LONGITUDE_TAG_NAME,
            self::LONGITUDE_REF_TAG_NAME,
            self::LATITUDE_TAG_NAME,
            self::LATITUDE_REF_TAG_NAME
        ]);
    }

    public function isComplete()
    {
        return $this->longitude !== null
            && $this->longitude_ref !== null
            && $this->latitude !== null
            && $this->latitude_ref !== null;
    }

    public function isCompleteComposite()
    {
        return $this->longitude !== null
            && $this->latitude !== null;
    }

    public function getCompositeLongitude()
    {
        return $this->longitude ;
    }

    public function getCompositeLatitude()
    {
        return $this->latitude;
    }

    public function getSignedLongitude()
    {
        if ($this->longitude === null) {
            return null;
        }
        return $this->longitude * ($this->longitude_ref === self::LONGITUDE_REF_WEST ? -1 : 1);
    }

    public function getSignedLatitude()
    {
        if ($this->latitude === null) {
            return null;
        }
        return $this->latitude * ($this->latitude_ref === self::LATITUDE_REF_SOUTH ? -1 : 1);
    }
}
