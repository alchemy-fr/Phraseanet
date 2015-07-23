<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;

class TextQueryHelper
{
    private function __construct() {}

    public static function filterCompatibleFields(array $fields, $query_text)
    {
        $is_numeric = is_numeric($query_text);
        $filtered = [];
        foreach ($fields as $field) {
            switch ($field->getType()) {
                case Mapping::TYPE_FLOAT:
                case Mapping::TYPE_DOUBLE:
                case Mapping::TYPE_INTEGER:
                case Mapping::TYPE_LONG:
                case Mapping::TYPE_SHORT:
                case Mapping::TYPE_BYTE:
                    if ($is_numeric) {
                        $filtered[] = $field;
                    }
                    break;
                case Mapping::TYPE_STRING:
                case Mapping::TYPE_DATE:
                default:
                    $filtered[] = $field;
            }
        }
        return $filtered;
    }
}
