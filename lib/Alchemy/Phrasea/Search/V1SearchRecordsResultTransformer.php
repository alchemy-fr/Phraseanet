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

class V1SearchRecordsResultTransformer extends V1SearchTransformer
{
    /**
     * @var RecordTransformer
     */
    private $recordTransformer;

    public function __construct(RecordTransformer $recordTransformer)
    {
        $this->recordTransformer = $recordTransformer;
    }

    public function includeResults(SearchResultView $resultView)
    {
        return $this->collection($resultView->getRecords(), $this->recordTransformer);
    }
}