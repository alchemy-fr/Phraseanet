<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Structure;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\RecordHelper;
use Assert\Assertion;

class ValueChecker
{
    private function __construct() {}

    public static function isValueCompatible(Typed $typed, $value)
    {
        return count(self::filterByValueCompatibility([$typed], $value)) > 0;
    }

    public static function filterByValueCompatibility(array $list, $value)
    {
        Assertion::allIsInstanceOf($list, Typed::class);
        $is_numeric = is_numeric($value);
        $is_valid_date = (RecordHelper::sanitizeDate($value) !== null);
        $filtered = [];
        foreach ($list as $item) {
            switch ($item->getType()) {
                case FieldMapping::TYPE_FLOAT:
                case FieldMapping::TYPE_DOUBLE:
                case FieldMapping::TYPE_INTEGER:
                case FieldMapping::TYPE_LONG:
                case FieldMapping::TYPE_SHORT:
                case FieldMapping::TYPE_BYTE:
//                    if ($is_numeric) {
                        $filtered[] = $item;
//                    }
                    break;
                case FieldMapping::TYPE_DATE:
//                    if ($is_valid_date) {
                        $filtered[] = $item;
//                    }
                    break;
                case FieldMapping::TYPE_STRING:
                default:
                    $filtered[] = $item;
            }
        }
        return $filtered;
    }
}
