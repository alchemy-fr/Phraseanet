<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Field;

class QueryHelper
{
    private function __construct() {}

    /**
     * @param Field[] $private_fields
     * @param Field[] $unrestricted_fields
     * @param \Closure $query_builder
     * @return array
     */
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
            $relevant_fields = [];
            foreach($unrestricted_fields as $uf) {
                foreach ($uf->getDependantCollections() as $c) {
                    if(in_array($c, $collections_map[$hash])) {
                        $relevant_fields[] = $uf;
                        break;
                    }
                }
            }
            $query = $query_builder(array_merge($fields, $relevant_fields));
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

    public static function getRangeFromDateString($value)
    {
        $date_from = null;
        $date_to   = null;
        try {
            $a = explode(';', preg_replace('/\D+/', ';', trim($value)));
            switch (count($a)) {
                case 1:     // yyyy
                    $date_to = clone($date_from = new \DateTime($a[0] . '-01-01 00:00:00'));    // will throw if date is not valid
                    $date_to->add(new \DateInterval('P1Y'));
                    break;
                case 2:     // yyyy;mm
                    $date_to = clone($date_from = new \DateTime($a[0] . '-' . $a[1] . '-01 00:00:00'));    // will throw if date is not valid
                    $date_to->add(new \DateInterval('P1M'));
                    break;
                case 3:     // yyyy;mm;dd
                    $date_to = clone($date_from = new \DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' 00:00:00'));    // will throw if date is not valid
                    $date_to->add(new \DateInterval('P1D'));
                    break;
                case 4:
                    $date_to = clone($date_from = new \DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':00:00'));
                    $date_to->add(new \DateInterval('PT1H'));
                    break;
                case 5:
                    $date_to = clone($date_from = new \DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':' . $a[4] . ':00'));
                    $date_to->add(new \DateInterval('PT1M'));
                    break;
                case 6:
                    $date_to = clone($date_from = new \DateTime($a[0] . '-' . $a[1] . '-' . $a[2] . ' ' . $a[3] . ':' . $a[4] . ':' . $a[5]));
                    // $date_to->add(new \DateInterval('PT1S'));    // no need since precision is 1 sec, a "equal" will be generated when from==to
                    break;
            }
        }
        catch (\Exception $e) {
            // no-op
        }

        if ($date_from === null || $date_to === null) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s".', $value));
        }

        return [
            'from' => $date_from->format('Y-m-d H:i:s'),
            'to'   => $date_to->format('Y-m-d H:i:s')
        ];
    }
}
