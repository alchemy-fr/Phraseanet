<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\Exception\RuntimeException;
use JsonSerializable;

class FacetsResponse implements JsonSerializable
{
    private $facets = array();

    public function __construct(array $response)
    {
        if (!isset($response['aggregations'])) {
            return;
        }
        foreach ($response['aggregations'] as $name => $aggregation) {
            if (!isset($aggregation['buckets'])) {
                $this->throwAggregationResponseError();
            }
            $values = $this->buildBucketsValues($name, $aggregation['buckets']);
            if ($values) {
                $this->facets[] = array(
                    'name' => $name,
                    'values' => $values,
                );
            }
        }
    }

    private function buildBucketsValues($name, $buckets)
    {
        $values = array();
        foreach ($buckets as $bucket) {
            if (!isset($bucket['key']) || !isset($bucket['doc_count'])) {
                $this->throwAggregationResponseError();
            }
            $values[] = array(
                'value' => $bucket['key'],
                'count' => $bucket['doc_count'],
                'query' => $this->buildQuery($name, $bucket['key']),
            );
        }

        return $values;
    }

    private function buildQuery($name, $value)
    {
        // Strip double quotes from values to prevent broken queries
        $value = str_replace('/"/u', ' ', $value);
        // TODO escape value when escaping is supported in query parser
        return ($name === 'Collection') ?
            sprintf('collection:"%s"', $value) :
            sprintf('"%s" IN %s', $value, $name);
    }

    private function throwAggregationResponseError()
    {
        throw new RuntimeException('Invalid aggregation response');
    }

    public function jsonSerialize()
    {
        return $this->facets;
    }
}
