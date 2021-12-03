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

use Alchemy\Phrasea\Utilities\NullableDateTime;

class V3StoryTransformer extends StoryTransformer
{
    /**
    * @var RecordTransformer
    */
    private $recordTransformer;


    /**
     * @var array
     */
    protected $availableIncludes = ['thumbnail', 'metadatas', 'children', 'caption'];

    /**
     * @var array
     */
    protected $defaultIncludes = ['thumbnail', 'metadatas'];

    /**
     * @param SubdefTransformer $subdefTransformer
     * @param RecordTransformer $recordTransformer
     */
    public function __construct(SubdefTransformer $subdefTransformer, RecordTransformer $recordTransformer)
    {
        parent::__construct($subdefTransformer, $recordTransformer);
        $this->recordTransformer = $recordTransformer;
    }

    public function transform(StoryView $storyView)
    {
        $story = $storyView->getStory();

        return [
            'databox_id' => $story->getDataboxId(),
            'story_id' => $story->getRecordId(),
            'cover_record_id' => $story->getCoverRecordId(),
            'updated_on' => NullableDateTime::format($story->getUpdated()),
            'created_on' => NullableDateTime::format($story->getUpdated()),
            'collection_id' => $story->getCollectionId(),
            'base_id' => $story->getBaseId(),
            'uuid' => $story->getUuid(),
            'children_offset' => $storyView->getData('childrenOffset'),
            'children_limit' => $storyView->getData('childrenLimit'),
            'children_count' => count($storyView->getChildren()),
            'children_total' => $storyView->getData('childrenCount')        // fix V1 wrong count
        ];
    }

    public function includeChildren(StoryView $storyView)
    {
        return $this->collection($storyView->getChildren(), $this->recordTransformer);
    }

}
