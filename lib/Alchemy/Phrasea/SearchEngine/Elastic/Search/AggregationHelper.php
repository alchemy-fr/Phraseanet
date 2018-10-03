<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

class AggregationHelper
{
    private function __construct() {}

    public static function wrapPrivateFieldAggregation(Field $field, array $aggregation)
    {
        if ($field->isPrivate()) {
            $wrapper = [];
            $wrapper['filter']['terms']['base_id'] = $field->getDependantCollections();
            $wrapper['aggs']['__wrapped_private_field__'] = $aggregation;
            return $wrapper;
        } else {
            return $aggregation;
        }
    }

    public static function unwrapPrivateFieldAggregation(array $aggregation)
    {
        if (isset($aggregation['__wrapped_private_field__'])) {
            return $aggregation['__wrapped_private_field__'];
        } else {
            return $aggregation;
        }
    }
}
