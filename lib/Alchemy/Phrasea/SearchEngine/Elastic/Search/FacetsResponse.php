<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Doctrine\Common\Collections\ArrayCollection;
use JsonSerializable;

class FacetsResponse implements JsonSerializable
{
    private $escaper;
    private $facets = array();

    public function __construct(Escaper $escaper, array $response)
    {
        $this->escaper = $escaper;

        if (!isset($response['aggregations'])) {
            return;
        }
        foreach ($response['aggregations'] as $name => $aggregation) {
            $aggregation = AggregationHelper::unwrapPrivateFieldAggregation($aggregation);
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

    /**
     * @return ArrayCollection
     */
    public function getAsSuggestions()
    {
        $suggestions = new ArrayCollection();

        // for es, suggestions are a flat view of facets (api backward compatibility)
        foreach ($this->facets as $facet) {
            foreach ($facet['values'] as $value) {
                $suggestions->add(new SearchEngineSuggestion($value['query'], $value['value'], $value['count']));
            }
        }

        return $suggestions;
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
        switch($name) {
            case 'Collection':
                $r = sprintf('collection:%s', $this->escaper->escapeWord($value));
                break;
            case "Base":
                $r = sprintf('base:%s', $this->escaper->escapeWord($value));
                break;
            default:
                $r = sprintf('r"%s" IN %s', $this->escaper->escapeRaw($value), $name);
                break;
        }
        return $r;
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
