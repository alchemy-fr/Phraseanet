<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Assert\Assertion;
use InvalidArgumentException;
use media_subdef;

class MetadataHelper
{
    private function __construct() {}

    public static function createTags()
    {
        static $tag_descriptors = [
            [media_subdef::TC_DATA_WIDTH             , 'integer', false],
            [media_subdef::TC_DATA_HEIGHT            , 'integer', false],
            [media_subdef::TC_DATA_COLORSPACE        , 'string' , false],
            [media_subdef::TC_DATA_CHANNELS          , 'integer', false],
            [media_subdef::TC_DATA_ORIENTATION       , 'integer', false],
            [media_subdef::TC_DATA_COLORDEPTH        , 'integer', false],
            [media_subdef::TC_DATA_DURATION          , 'float'  , false],
            [media_subdef::TC_DATA_AUDIOCODEC        , 'string' , false],
            [media_subdef::TC_DATA_AUDIOSAMPLERATE   , 'float'  , false],
            [media_subdef::TC_DATA_VIDEOCODEC        , 'string' , false],
            [media_subdef::TC_DATA_FRAMERATE         , 'float'  , false],
            [media_subdef::TC_DATA_MIMETYPE          , 'string' , false],
            [media_subdef::TC_DATA_FILESIZE          , 'long'   , false],
            // TODO use geo point type for lat/long
            [media_subdef::TC_DATA_LONGITUDE         , 'float'  , false],
            [media_subdef::TC_DATA_LATITUDE          , 'float'  , false],
            [media_subdef::TC_DATA_FOCALLENGTH       , 'float'  , false],
            [media_subdef::TC_DATA_CAMERAMODEL       , 'string' , true ],
            [media_subdef::TC_DATA_FLASHFIRED        , 'boolean', false],
            [media_subdef::TC_DATA_APERTURE          , 'float'  , false],
            [media_subdef::TC_DATA_SHUTTERSPEED      , 'float'  , false],
            [media_subdef::TC_DATA_HYPERFOCALDISTANCE, 'float'  , false],
            [media_subdef::TC_DATA_ISO               , 'integer', false],
            [media_subdef::TC_DATA_LIGHTVALUE        , 'float'  , false]
        ];

        $tags = [];
        foreach ($tag_descriptors as $descriptor) {
            $tags[] = new Tag($descriptor[0], $descriptor[1], $descriptor[2]);
        }

        return $tags;
    }
}
