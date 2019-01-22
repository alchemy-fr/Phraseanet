<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Assert\Assertion;
use InvalidArgumentException;
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
            if (array_key_exists('type', $descriptor) && array_key_exists('analyzable', $descriptor)) {
                $tags[] = new Tag($key, $descriptor['type'], $descriptor['analyzable']);
            }
        }

        return $tags;
    }
}
