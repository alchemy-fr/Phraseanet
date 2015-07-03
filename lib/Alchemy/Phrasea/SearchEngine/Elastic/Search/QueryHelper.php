<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

class QueryHelper
{
    private function __construct() {}

    /**
     * Apply conjunction or disjunction between a query and a sub query clause
     *
     * @param  array  $query     Query
     * @param  string $type      "must" for conjunction, "should" for disjunction
     * @param  array  $sub_query Clause query
     * @return array             Resulting query
     */
    public static function applyBooleanClause(array $query, $type, array $clause)
    {
        if (!in_array($type, ['must', 'should'])) {
            throw new \InvalidArgumentException(sprintf('Type must be either "must" or "should", "%s" given', $type));
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
            $query['bool'][$type] = $clause;

            return $query;
        }
    }
}
