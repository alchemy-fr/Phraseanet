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

use League\Fractal\TransformerAbstract;

class V1SearchCompositeResultTransformer extends TransformerAbstract
{
    /**
     * @var string[]
     */
    protected $availableIncludes = ['stories', 'records'];

    /**
     * @var string[]
     */
    protected $defaultIncludes = ['stories', 'records'];

    /**
     * @var RecordTransformer
     */
    private $recordTransformer;

    /**
     * @var StoryTransformer
     */
    private $storyTransformer;

    public function __construct(RecordTransformer $recordTransformer, StoryTransformer $storyTransformer)
    {
        $this->recordTransformer = $recordTransformer;
        $this->storyTransformer = $storyTransformer;
    }

    public function transform()
    {
        return [];
    }

    public function includeRecords(SearchResultView $resultView)
    {
        return $this->collection($resultView->getRecords(), $this->recordTransformer);
    }

    public function includeStories(SearchResultView $resultView)
    {
        return $this->collection($resultView->getStories(), $this->storyTransformer);
    }
}
