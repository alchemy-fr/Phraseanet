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

class RecordTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['thumbnail', 'technical_informations', 'subdefs', 'metadata', 'status', 'caption'];
    protected $defaultIncludes = ['thumbnail', 'technical_informations'];

    /**
     * @var SubdefTransformer
     */
    private $subdefTransformer;

    /**
     * @var TechnicalDataTransformer
     */
    private $technicalDataTransformer;

    public function __construct(SubdefTransformer $subdefTransformer, TechnicalDataTransformer $technicalDataTransformer)
    {
        $this->subdefTransformer = $subdefTransformer;
        $this->technicalDataTransformer = $technicalDataTransformer;
    }

    public function transform(RecordView $recordView)
    {
        $record = $recordView->getRecord();

        return [
            'databox_id' => $record->getDataboxId(),
            'record_id' => $record->getRecordId(),
            'mime_type' => $record->getMimeType(),
            'title' => $record->get_title(),
            'original_name' => $record->get_original_name(),
            'updated_on' => $record->getUpdated()->format(DATE_ATOM),
            'created_on' => $record->getCreated()->format(DATE_ATOM),
            'collection_id' => $record->getCollectionId(),
            'base_id' => $record->getBaseId(),
            'sha256' => $record->getSha256(),
            'phrasea_type' => $record->getType(),
            'uuid' => $record->getUuid(),
        ];
    }

    public function includeThumbnail(RecordView $recordView)
    {
        return $this->item($recordView->getSubdef('thumbnail'), $this->subdefTransformer);
    }

    public function includeTechnicalInformations(RecordView $recordView)
    {
        return $this->collection($recordView->getTechnicalDataView()->getDataSet(), $this->technicalDataTransformer);
    }

    public function includeSubdefs(RecordView $recordView)
    {
        return $this->collection($recordView->getSubdefs(), $this->subdefTransformer);
    }

    public function includeMetadata(RecordView $recordView)
    {
        $ret = [];

        foreach ($recordView->getCaption()->getCaption()->get_fields() as $field) {
            $databox_field = $field->get_databox_field();

            $fieldData = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'labels' => [
                    'fr' => $databox_field->get_label('fr'),
                    'en' => $databox_field->get_label('en'),
                    'de' => $databox_field->get_label('de'),
                    'nl' => $databox_field->get_label('nl'),
                ],
            ];

            foreach ($field->get_values() as $value) {
                $data = [
                    'meta_id' => $value->getId(),
                    'value' => $value->getValue(),
                ];

                $ret[] = array_replace($fieldData, $data);
            }
        }

        return $this->collection($recordView->getCaption(), )
    }

    public function includeStatus(RecordView $recordView)
    {
    }

    public function includeCaption(RecordView $recordView)
    {
    }
}
