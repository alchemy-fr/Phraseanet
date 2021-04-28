<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use media_subdef;

class MetadataHelper
{
    private static $tag_descriptors = [];

    private function __construct() {}

    public static function createTags()
    {
        if (empty(self::$tag_descriptors)) {
            self::$tag_descriptors = media_subdef::getTechnicalFieldsList();
        }

        $tags = [];
        foreach (self::$tag_descriptors as $key => $descriptor) {
            if (array_key_exists('type', $descriptor)) {
                $tags[] = new Tag($key, $descriptor['type']);
            }
        }

        return $tags;
    }
}
