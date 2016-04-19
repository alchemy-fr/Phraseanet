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
        $thumbnailView = $recordView->getSubdef('thumbnail');

        return $thumbnailView ? $this->item($thumbnailView, $this->subdefTransformer) : null;
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
        $fieldData = [];
        $values = [];

        foreach ($recordView->getCaption()->getFields() as $field) {
            $databox_field = $field->get_databox_field();

            $fieldData[$field->get_meta_struct_id()] = [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'labels' => [
                    'fr' => $databox_field->get_label('fr'),
                    'en' => $databox_field->get_label('en'),
                    'de' => $databox_field->get_label('de'),
                    'nl' => $databox_field->get_label('nl'),
                ],
            ];

            $values[] = $field->get_values();
        }

        if ($values) {
            $values = call_user_func_array('array_merge', $values);
        }

        return $this->collection($values, function (\caption_Field_Value $value) use ($fieldData) {
            $data = $fieldData[$value->getDatabox_field()->get_id()];

            $data['meta_id'] = $value->getId();
            $data['value'] = $value->getValue();

            return $data;
        });
    }

    public function includeStatus(RecordView $recordView)
    {
        $data = [];

        $bitMask = $recordView->getRecord()->getStatusBitField();

        foreach ($recordView->getRecord()->getDatabox()->getStatusStructure() as $bit => $status) {
            $data[] = [
                'bit' => $bit,
                'mask' => $bitMask,
            ];
        }

        return $this->collection($data, function (array $bitData) {
            return [
                'bit' => $bitData['bit'],
                'state' => \databox_status::bitIsSet($bitData['mask'], $bitData['bit']),
            ];
        });
    }

    public function includeCaption(RecordView $recordView)
    {
        return $this->collection($recordView->getCaption()->getFields(), function (\caption_field $field) {
            return [
                'meta_structure_id' => $field->get_meta_struct_id(),
                'name' => $field->get_name(),
                'value' => $field->get_serialized_values(';'),
            ];
        });
    }
}
