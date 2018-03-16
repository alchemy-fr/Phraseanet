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
        self::$tag_descriptors = media_subdef::getTechnicalFieldsList();

        $tags = [];
        foreach (self::$tag_descriptors as $descriptor) {
            if (array_key_exists('type', $descriptor) && array_key_exists('analyzable', $descriptor) && array_key_exists('name', $descriptor)) {
                $tags[] = new Tag($descriptor['name'], $descriptor['type'], $descriptor['analyzable']);
            }
        }

        return $tags;
    }
}
