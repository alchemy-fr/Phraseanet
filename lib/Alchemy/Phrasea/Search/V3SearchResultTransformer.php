<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Alchemy\Phrasea\SearchEngine\SearchEngineSuggestion;
use League\Fractal\TransformerAbstract;

class V3SearchResultTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['results', 'suggestions', 'facets'];
    protected $defaultIncludes = ['results'];

    /**
     * @var TransformerAbstract
     */
    private $transformer;

    public function __construct(TransformerAbstract $transformer)
    {
        $this->transformer = $transformer;
    }

    public function transform(SearchResultView $resultView)
    {
        $result = $resultView->getResult();

        return [
            'query' => $result->getQueryText(),
            'offset' => $result->getOptions()->getFirstResult(),
            'limit' => $result->getOptions()->getMaxResults(),
            'count' => $result->getAvailable(),
            'total' => $result->getTotal(),
            'error' => (string)$result->getError(),
            'warning' => (string)$result->getWarning(),
            'query_time' => $result->getDuration(),
            'search_indexes' => $result->getIndexes(),
            // 'facets' => $result->getFacets(),
        ];
    }

    public function includeResults(SearchResultView $resultView)
    {
        return $this->item($resultView, $this->transformer);
    }

    public function includeSuggestions(SearchResultView $resultView)
    {
        return $this->collection($resultView->getResult()->getSuggestions()->toArray(), function ($x) {
            return $x->toArray();
        });
    }

    public function includeFacets(SearchResultView $resultView)
    {
        return $this->item($resultView->getResult()->getFacets(), function ($x) {
            return $x;
        });
    }
}
