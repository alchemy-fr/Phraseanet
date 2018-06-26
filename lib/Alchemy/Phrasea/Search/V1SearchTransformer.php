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

abstract class V1SearchTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['results'];
    protected $defaultIncludes = ['results'];

    public function transform(SearchResultView $resultView)
    {
        $result = $resultView->getResult();

        return [
            'offset_start' => $result->getOptions()->getFirstResult(),
            'per_page' => $result->getOptions()->getMaxResults(),
            'available_results' => $result->getAvailable(),
            'total_results' => $result->getTotal(),
            'error' => (string)$result->getError(),
            'warning' => (string)$result->getWarning(),
            'query_time' => $result->getDuration(),
            'search_indexes' => $result->getIndexes(),
            'suggestions' => array_map(
                function (SearchEngineSuggestion $suggestion) {
                    return $suggestion->toArray();
                }, $result->getSuggestions()->toArray()),
            'facets' => $result->getFacets(),
            'query' => $result->getQueryText(),
        ];
    }

    abstract public function includeResults(SearchResultView $resultView);
}
