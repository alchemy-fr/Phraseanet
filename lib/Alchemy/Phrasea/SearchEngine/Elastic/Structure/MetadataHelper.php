<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use media_subdef;

class MetadataHelper
{
    private function __construct() {}

    public static function createTags()
    {
        static $tag_descriptors = [
            [media_subdef::TC_DATA_WIDTH             , FieldMapping::TYPE_INTEGER ],
            [media_subdef::TC_DATA_HEIGHT            , FieldMapping::TYPE_INTEGER ],
            [media_subdef::TC_DATA_COLORSPACE        , FieldMapping::TYPE_KEYWORD ],
            [media_subdef::TC_DATA_CHANNELS          , FieldMapping::TYPE_INTEGER ],
            [media_subdef::TC_DATA_ORIENTATION       , FieldMapping::TYPE_INTEGER ],
            [media_subdef::TC_DATA_COLORDEPTH        , FieldMapping::TYPE_INTEGER ],
            [media_subdef::TC_DATA_DURATION          , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_AUDIOCODEC        , FieldMapping::TYPE_KEYWORD ],
            [media_subdef::TC_DATA_AUDIOSAMPLERATE   , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_VIDEOCODEC        , FieldMapping::TYPE_KEYWORD ],
            [media_subdef::TC_DATA_FRAMERATE         , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_MIMETYPE          , FieldMapping::TYPE_KEYWORD ],
            [media_subdef::TC_DATA_FILESIZE          , FieldMapping::TYPE_LONG    ],
            // TODO use geo point type for lat/long
            [media_subdef::TC_DATA_LONGITUDE         , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_LATITUDE          , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_FOCALLENGTH       , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_CAMERAMODEL       , FieldMapping::TYPE_TEXT    ],
            [media_subdef::TC_DATA_FLASHFIRED        , FieldMapping::TYPE_BOOLEAN ],
            [media_subdef::TC_DATA_APERTURE          , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_SHUTTERSPEED      , FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_HYPERFOCALDISTANCE, FieldMapping::TYPE_FLOAT   ],
            [media_subdef::TC_DATA_ISO               , FieldMapping::TYPE_INTEGER ],
            [media_subdef::TC_DATA_LIGHTVALUE        , FieldMapping::TYPE_FLOAT   ]
        ];

        $tags = [];
        foreach ($tag_descriptors as $descriptor) {
            $tags[] = new Tag($descriptor[0], $descriptor[1]);
        }

        return $tags;
    }
}
