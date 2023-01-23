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

class V3StoryTransformer extends RecordTransformer
{
    /**
    * @var RecordTransformer
    */
    private $recordTransformer;


    /**
     * @var array
     */
    protected $availableIncludes = [
        'thumbnail',
        'subdefs',
        'metadata',
        'status',
        'caption',
        'children'
    ];

    /**
     * @var array
     */
    protected $defaultIncludes = [
        'thumbnail',
    ];

    /**
     * @param RecordTransformer $recordTransformer
     */
    public function __construct(RecordTransformer $recordTransformer)
    {
        $this->recordTransformer = $recordTransformer;
    }

    public function transform($storyView)
    {
        /** @var StoryView $storyView */
        $story = $storyView->getStory();

        $r = $this->recordTransformer->transform($storyView);
        $r['story_id'] = $r['record_id'];
        unset($r['record_id']);

        return array_merge(
            $r,
            [
                'cover_record_id' => $story->getCoverRecordId(),
                'children_offset' => $storyView->getData('childrenOffset'),
                'children_limit'  => $storyView->getData('childrenLimit'),
                'children_count'  => count($storyView->getChildren()),
                'children_total'  => $storyView->getData('childrenCount')        // fix V1 wrong count
            ]
        );
    }

    protected function getSubdefTransformer()
    {
        return $this->recordTransformer->getSubdefTransformer();
    }

    protected function getTechnicalDataTransformer()
    {
        return $this->recordTransformer->getTechnicalDataTransformer();
    }


    public function includeChildren(StoryView $storyView)
    {
        return $this->collection($storyView->getChildren(), $this->recordTransformer);
    }

}
