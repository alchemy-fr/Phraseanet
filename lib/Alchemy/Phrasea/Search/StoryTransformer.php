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
use League\Fractal\TransformerAbstract;

class StoryTransformer extends TransformerAbstract
{
    /**
     * @var array
     */
    protected $availableIncludes = ['thumbnail', 'metadatas', 'records', 'caption'];

    /**
     * @var array
     */
    protected $defaultIncludes = ['thumbnail', 'metadatas'];

    /**
     * @var SubdefTransformer
     */
    private $subdefTransformer;

    /**
     * @var RecordTransformer
     */
    private $recordTransformer;

    /**
     * @param SubdefTransformer $subdefTransformer
     * @param RecordTransformer $recordTransformer
     */
    public function __construct(SubdefTransformer $subdefTransformer, RecordTransformer $recordTransformer)
    {
        $this->subdefTransformer = $subdefTransformer;
        $this->recordTransformer = $recordTransformer;
    }

    public function transform(StoryView $storyView)
    {
        $story = $storyView->getStory();

        return [
            '@entity@' => 'http://api.phraseanet.com/api/objects/story',
            'databox_id' => $story->getDataboxId(),
            'story_id' => $story->getRecordId(),
            'cover_record_id' => $story->getCoverRecordId(),
            'updated_on' => NullableDateTime::format($story->getUpdated()),
            'created_on' => NullableDateTime::format($story->getUpdated()),
            'collection_id' => $story->getCollectionId(),
            'base_id' => $story->getBaseId(),
            'uuid' => $story->getUuid(),
            'record_count' => count($storyView->getChildren())
        ];
    }

    public function includeThumbnail(StoryView $storyView)
    {
        return $this->item($storyView->getSubdef('thumbnail'), $this->subdefTransformer);
    }

    public function includeMetadatas(StoryView $storyView)
    {
        return $this->item($storyView->getCaption(), $this->getCaptionDCFieldTransformer());
    }

    public function includeRecords(StoryView $storyView)
    {
        return $this->collection($storyView->getChildren(), $this->recordTransformer);
    }

    public function includeCaption(StoryView $storyView)
    {
        return $this->collection($storyView->getCaption()->getFields(), $this->getCaptionFieldTransformer());
    }

    /**
     * @return \Closure
     */
    private function getCaptionFieldTransformer()
    {
        return function (\caption_field $captionField) {
            return [
                'meta_structure_id' => $captionField->get_meta_struct_id(),
                'name' => $captionField->get_name(),
                'value' => $captionField->get_serialized_values(';')
            ];
        };
    }

    /**
     * @return \Closure
     */
    private function getCaptionDCFieldTransformer()
    {
        /**
         * @param \caption_field[] $fields
         * @param string $dcField
         * @return string|null
         */
        $format = function ($fields, $dcField) {
            return isset($fields[$dcField]) ? $fields[$dcField]->get_serialized_values() : null;
        };

        return function (CaptionView $captionView) use ($format) {
            $caption = $captionView->getCaption()->getDCFields();

            return [
                '@entity@' => 'http://api.phraseanet.com/api/objects/story-metadata-bag',
                'dc:contributor' => $format($caption, \databox_Field_DCESAbstract::Contributor),
                'dc:coverage' => $format($caption, \databox_Field_DCESAbstract::Coverage),
                'dc:creator' => $format($caption, \databox_Field_DCESAbstract::Creator),
                'dc:date' => $format($caption, \databox_Field_DCESAbstract::Date),
                'dc:description' => $format($caption, \databox_Field_DCESAbstract::Description),
                'dc:format' => $format($caption, \databox_Field_DCESAbstract::Format),
                'dc:identifier' => $format($caption, \databox_Field_DCESAbstract::Identifier),
                'dc:language' => $format($caption, \databox_Field_DCESAbstract::Language),
                'dc:publisher' => $format($caption, \databox_Field_DCESAbstract::Publisher),
                'dc:relation' => $format($caption, \databox_Field_DCESAbstract::Relation),
                'dc:rights' => $format($caption, \databox_Field_DCESAbstract::Rights),
                'dc:source' => $format($caption, \databox_Field_DCESAbstract::Source),
                'dc:subject' => $format($caption, \databox_Field_DCESAbstract::Subject),
                'dc:title' => $format($caption, \databox_Field_DCESAbstract::Title),
                'dc:type' => $format($caption, \databox_Field_DCESAbstract::Type),
            ];
        };
    }
}
