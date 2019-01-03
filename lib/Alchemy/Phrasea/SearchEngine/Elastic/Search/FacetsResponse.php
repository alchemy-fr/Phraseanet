<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\Structure;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Doctrine\Common\Collections\ArrayCollection;
use igorw;

class FacetsResponse
{
    private $escaper;
    private $facets = array();

    public function __construct(Escaper $escaper, array $response, GlobalStructure $structure)
    {
        $this->escaper = $escaper;

        if (!isset($response['aggregations'])) {
            return;
        }

        $atf = ElasticsearchOptions::getAggregableTechnicalFields();

        foreach ($response['aggregations'] as $name => $aggregation) {
            $tf = null;
            $valueFormatter = function($v){ return $v; };    // default equality formatter

            if(array_key_exists($name, $atf)) {
                $tf = $atf[$name];
                if(array_key_exists('output_formatter', $tf)) {
                    $valueFormatter = $tf['output_formatter'];
                }
            }

            $aggregation = AggregationHelper::unwrapPrivateFieldAggregation($aggregation);
            if (!isset($aggregation['buckets'])) {
                $this->throwAggregationResponseError();
            }

            $values = [];
            foreach ($aggregation['buckets'] as $bucket) {
                if (!isset($bucket['key']) || !isset($bucket['doc_count'])) {
                    $this->throwAggregationResponseError();
                }
                if($tf) {
                    // the field is one of the hardcoded tech fields
                    $value = [
                        'value'     => $valueFormatter($bucket['key']),
                        'raw_value' => $bucket['key'],
                        'count'     => $bucket['doc_count'],
                        'query'     => sprintf($tf['query'], $this->escaper->escapeWord($bucket['key']))
                    ];
                }
                else {
                    // the field is a normal field
                    $value = [
                        'value'     => $bucket['key'],
                        'raw_value' => $bucket['key'],
                        'count'     => $bucket['doc_count'],
                        'query'     => sprintf('field.%s:%s', $this->escaper->escapeWord($name), $this->escaper->escapeWord($bucket['key']))
                    ];
                }

                $values[] = $value;
            }

            if (count($values) > 0) {
                $this->facets[] = [
                    // 'type' => $tf ? $tf['type'] : null,
                    'name' => $name,
                    'field' => $tf ? $tf['field'] : sprintf('field.%s', $name),
                    'values' => $values,
                ];
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

    private function throwAggregationResponseError()
    {
        throw new RuntimeException('Invalid aggregation response');
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->facets;
    }
}
