<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\FieldMapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Mapping;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

class QueryHelper
{
    private function __construct() {}

    public static function wrapPrivateFieldQueries(array $private_fields, array $unrestricted_fields, \Closure $query_builder)
    {
        // We make a boolean clause for each collection set to shrink query size
        // (instead of a clause for each field, with his collection set)
        $fields_map = [];
        $collections_map = [];
        foreach ($private_fields as $field) {
            $collections = $field->getDependantCollections();
            $hash = self::hashCollections($collections);
            $collections_map[$hash] = $collections;
            if (!isset($fields_map[$hash])) {
                $fields_map[$hash] = [];
            }
            // Merge fields with others having the same collections
            $fields_map[$hash][] = $field;
        }

        $queries = [];
        foreach ($fields_map as $hash => $fields) {
            // Right to query on a private field is dependant of document collection
            // Here we make sure we can only match on allowed collections
            $query = $query_builder(array_merge($fields, $unrestricted_fields));
            if ($query !== null) {
                $queries[] = self::restrictQueryToCollections($query, $collections_map[$hash]);
            }
        }

        return $queries;
    }

    public static function wrapPrivateFieldQuery(Field $field, array $query)
    {
        if ($field->isPrivate()) {
            return self::restrictQueryToCollections($query, $field->getDependantCollections());
        } else {
            return $query;
        }
    }

    private static function restrictQueryToCollections(array $query, array $collections)
    {
        $wrapper = [];
        $wrapper['filtered']['filter']['terms']['base_id'] = $collections;
        $wrapper['filtered']['query'] = $query;
        return $wrapper;
    }

    private static function hashCollections(array $collections)
    {
        sort($collections, SORT_REGULAR);
        return implode('|', $collections);
    }

    /**
     * Apply conjunction or disjunction between a query and a sub query clause
     *
     * @param  array  $query     Query
     * @param  string $type      "must" for conjunction, "should" for disjunction
     * @param  array  $sub_query Clause query
     * @return array             Resulting query
     */
    public static function applyBooleanClause($query, $type, array $clause)
    {
        if (!in_array($type, ['must', 'should'])) {
            throw new \InvalidArgumentException(sprintf('Type must be either "must" or "should", "%s" given', $type));
        }

        if ($query === null) {
            return $clause;
        }

        if (!is_array($query)) {
            throw new \InvalidArgumentException(sprintf('Query must be either an array or null, "%s" given', gettype($query)));
        }

        if (!isset($query['bool'])) {
            // Wrap in a boolean query
            $bool = [];
            $bool['bool'][$type][] = $query;
            $bool['bool'][$type][] = $clause;

            return $bool;
        } elseif (isset($query['bool'][$type])) {
            // Reuse the existing boolean clause group
            if (!is_array($query['bool'][$type])) {
                // Wrap the previous clause in an array
                $previous_clause = $query['bool'][$type];
                $query['bool'][$type] = [];
                $query['bool'][$type][] = $previous_clause;
            }
            $query['bool'][$type][] = $clause;

            return $query;
        } else {
            $query['bool'][$type][] = $clause;

            return $query;
        }
    }

    public static function getRangeFromDateString($string)
    {
        $formats = ['Y/m/d', 'Y/m', 'Y'];
        $deltas = ['+1 day', '+1 month', '+1 year'];
        $to = null;
        while ($format = array_pop($formats)) {
            $delta = array_pop($deltas);
            $from = date_create_from_format($format, $string);
            if ($from !== false) {
                // Rewind to start of range
                $month = 1;
                $day = 1;
                switch ($format) {
                    case 'Y/m/d':
                        $day = (int) $from->format('d');
                    case 'Y/m':
                        $month = (int) $from->format('m');
                    case 'Y':
                        $year = (int) $from->format('Y');
                }
                date_date_set($from, $year, $month, $day);
                date_time_set($from, 0, 0, 0);
                // Create end of the the range
                $to = date_modify(clone $from, $delta);
                break;
            }
        }

        if (!$from || !$to) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s".', $string));
        }

        return [
            'from' => $from->format(FieldMapping::DATE_FORMAT_CAPTION_PHP),
            'to'   => $to->format(FieldMapping::DATE_FORMAT_CAPTION_PHP)
        ];
    }
}
