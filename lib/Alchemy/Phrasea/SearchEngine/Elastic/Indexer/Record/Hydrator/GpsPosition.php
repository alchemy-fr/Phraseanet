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

use Assert\Assertion;

class GpsPosition
{
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

    public function set($tag_name, $value)
    {
        switch ($tag_name) {
            case self::LONGITUDE_TAG_NAME:
                Assertion::numeric($value);
                $this->longitude = (float) $value;
                break;

            case self::LATITUDE_TAG_NAME:
                Assertion::numeric($value);
                $this->latitude = (float) $value;
                break;

            case self::LONGITUDE_REF_TAG_NAME:
                $normalized = strtoupper($value);
                if ($normalized !== self::LONGITUDE_REF_EAST && $normalized !== self::LONGITUDE_REF_WEST) {
                    throw new \InvalidArgumentException(sprintf('Invalid longitude reference "%s" (expecting "%s" or "%s").', $value, self::LONGITUDE_REF_EAST, self::LONGITUDE_REF_WEST));
                }
                $this->longitude_ref = $value;
                break;

            case self::LATITUDE_REF_TAG_NAME:
                $normalized = strtoupper($value);
                if ($normalized !== self::LATITUDE_REF_NORTH && $normalized !== self::LATITUDE_REF_SOUTH) {
                    throw new \InvalidArgumentException(sprintf('Invalid latitude reference "%s" (expecting "%s" or "%s").', $value, self::LATITUDE_REF_NORTH, self::LATITUDE_REF_SOUTH));
                }
                $this->latitude_ref = $normalized;
                break;

            default:
                throw new \InvalidArgumentException(sprintf('Unsupported tag name "%s".', $tag_name));
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
