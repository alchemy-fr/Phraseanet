<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

class QueryHelper
{
    private function __construct() {}

    public static function buildPrivateFieldQueries(QueryContext $context, \Closure $matcher_callback, \Closure $index_fields_callback = null)
    {
        if ($index_fields_callback === null) {
            $index_fields_callback = function (Field $field) use ($context) {
                return $context->localizeField($field);
            };
        }

        // We make a boolean clause for each collection set to shrink query size
        // (instead of a clause for each field, with his collection set)
        $fields_map = [];
        $collections_map = [];
        foreach ($context->getPrivateFields() as $field) {
            $collections = $field->getDependantCollections();
            $hash = self::hashCollections($collections);
            $collections_map[$hash] = $collections;
            if (!isset($fields_map[$hash])) {
                $fields_map[$hash] = [];
            }
            // Merge fields with others having the same collections
            $fields = (array) $index_fields_callback($field);
            foreach ($fields as $fields_map[$hash][]);
        }

        $queries = [];
        foreach ($fields_map as $hash => $fields) {
            // Right to query on a private field is dependant of document collection
            // Here we make sure we can only match on allowed collections
            $queries[] = self::restrictQueryToCollections(
                $matcher_callback($fields),
                $collections_map[$hash]
            );
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
        $wrapper['bool']['must'][0]['terms']['base_id'] = $collections;
        $wrapper['bool']['must'][1] = $query;
        return $wrapper;
    }

    private static function hashCollections(array $collections)
    {
        sort($collections, SORT_REGULAR);
        return implode('|', $collections);
    }

    /**
     * @todo Factor with buildPrivateFieldQueries()
     */
    public static function buildPrivateFieldConceptQueries(QueryContext $context, \Closure $matchers_callback)
    {
        // We make a boolean clause for each collection set to shrink query size
        // (instead of a clause for each field, with his collection set)
        $fields_map = [];
        $collections_map = [];
        foreach ($context->getPrivateFields() as $field) {
            $collections = $field->getDependantCollections();
            $hash = self::hashCollections($collections);
            $collections_map[$hash] = $collections;
            if (!isset($fields_map[$hash])) {
                $fields_map[$hash] = [];
            }
            // Merge fields with others having the same collections
            $fields_map[$hash][] = $field->getConceptPathIndexField();
        }

        $queries = [];
        foreach ($fields_map as $hash => $fields) {
            // Right to query on a private field is dependant of document collection
            // Here we make sure we can only match on allowed collections
            $query = [];
            $query['bool']['must'][0]['terms']['base_id'] = $collections_map[$hash];
            foreach ($matchers_callback($fields) as $concept_query) {
                $query = self::applyBooleanClause($query, 'should', $concept_query);
            }
            $queries[] = $query;
        }

        return $queries;
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
}
