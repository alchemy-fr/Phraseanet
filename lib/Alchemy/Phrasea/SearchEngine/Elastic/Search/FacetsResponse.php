<?php

namespace Alchemy\Phrasea\SearchEngine\Elastic\Search;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\SearchEngine\Elastic\ElasticsearchOptions;
use Alchemy\Phrasea\SearchEngine\Elastic\Structure\GlobalStructure;
use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Translation\TranslatorInterface;

class FacetsResponse
{
    private $escaper;
    private $facets = array();
    private $translator;

    public function __construct(ElasticsearchOptions $options, Escaper $escaper, TranslatorInterface $translator, array $response, GlobalStructure $structure)
    {
        $this->escaper = $escaper;
        $this->translator = $translator;

        if (!isset($response['aggregations'])) {
            return;
        }

        $atf = ElasticsearchOptions::getAggregableTechnicalFields($this->translator);

        // sort facets respecting the order defined in options
        foreach($options->getAggregableFields() as $name=>$foptions) {
            if(!array_key_exists($name, $response['aggregations'])) {
                continue;
            }
            $aggregation = $response['aggregations'][$name];

            $tf = null;
            $valueFormatter = function($v){ return $v; };    // default equality formatter
            $label = $name;

            if(array_key_exists($name, $atf)) {
                $tf = $atf[$name];
                $label = $tf['label'];
                if(array_key_exists('output_formatter', $tf)) {
                    $valueFormatter = $tf['output_formatter'];
                }
            }

            $aggregation = AggregationHelper::unwrapPrivateFieldAggregation($aggregation);
            if (!isset($aggregation['buckets'])) {
                $this->throwAggregationResponseError();
            }

            $values = [];

            // insert a fake bucket from the "empty" agg ?
            if(array_key_exists($name.'#empty', $response['aggregations'])) {
                if (!empty($aggregation['buckets'])) {      // don't add to a field with no aggs (no buckets), since it will enforce display of the irrelevant facet
                    if($response['aggregations'][$name . '#empty']['doc_count'] > 0) {  // don't add a facet for 0 results
                        $aggregation['buckets'][] = [
                            'key'       => '_unset_',
                            'value'     => $this->translator->trans('prod:workzone:facetstab:unset_field_facet_label_(%fieldname%)', ['%fieldname%' =>$label]),   // special homemade prop to display a human value instead of the key
 //                           'value'     => 'unset '.$name,   // special homemade prop to display a human value instead of the key
                            'doc_count' => $response['aggregations'][$name . '#empty']['doc_count']
                        ];
                    }
                }
            }

            foreach ($aggregation['buckets'] as $bucket) {
                if (!isset($bucket['key']) || !isset($bucket['doc_count'])) {
                    $this->throwAggregationResponseError();
                }
                $key = array_key_exists('key_as_string', $bucket) ? $bucket['key_as_string'] : $bucket['key'];
                if($tf) {
                    // the field is one of the hardcoded tech fields
                    if($key == '_unset_' && array_key_exists('value', $bucket)) {
                        // don't use the valueformatter since 'value' if already translated
                        $v = $bucket['value'];
                    }
                    else {
                        $v = $valueFormatter($key);
                    }
                    $value = [
                        'value'     => $v,
                        'raw_value' => $key,
                        'count'     => $bucket['doc_count'],
                        'query'     => sprintf($tf['query'], $this->escaper->escapeWord($key))
                    ];
                }
                else {
                    // the field is a normal field
                    $value = [
                        'value'     => array_key_exists('value', $bucket) ? $bucket['value'] : $key,
                        'raw_value' => $key,
                        'count'     => $bucket['doc_count'],
                        'query'     => sprintf('field.%s=%s', $this->escaper->escapeWord($name), $this->escaper->quoteWord($key))
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
