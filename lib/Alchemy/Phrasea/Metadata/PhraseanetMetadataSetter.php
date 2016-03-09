<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use Alchemy\Phrasea\Databox\DataboxRepository;
use PHPExiftool\Driver\Metadata\Metadata;

class PhraseanetMetadataSetter
{
    /**
     * @var DataboxRepository
     */
    private $repository;

    public function __construct(DataboxRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Metadata[] $metadataCollection
     * @param \record_adapter $record
     * @throws \Exception_InvalidArgument
     */
    public function replaceMetadata($metadataCollection, \record_adapter $record)
    {
        $metaStructure = $this->repository->find($record->getDataboxId())->get_meta_structure();

        $metadataPerField = $this->extractMetadataPerField($metaStructure, $metadataCollection);

        $metadataInRecordFormat = [];

        foreach ($metaStructure as $field) {
            $fieldName = $field->get_name();

            if (!isset($metadataPerField[$fieldName])) {
                continue;
            }

            $this->deleteCaptionValues($record, $fieldName);

            $values = $metadataPerField[$fieldName];

            if ($field->is_multi()) {
                $values = $this->getUniqueValues($values, $field->get_separator());
            }

            $data = [
                'meta_struct_id' => $field->get_id(),
                'meta_id' => null,
            ];

            foreach ($values as $value) {
                if (trim($value) === '') {
                    continue;
                }

                $data['value'] = $value;

                $metadataInRecordFormat[] = $data;
            }
        }

        if (! empty($metadataInRecordFormat)) {
            $record->set_metadatas($metadataInRecordFormat, true);
        }
    }

    /**
     * @param \databox_field[] $metaStructure
     * @return array<string,array<string>>
     */
    private function groupDataboxFieldNamesPerTagName($metaStructure)
    {
        $groups = [];

        foreach ($metaStructure as $databoxField) {
            $tagName = $databoxField->get_tag()->getTagname();

            if (!isset($groups[$tagName])) {
                $groups[$tagName] = [];
            }

            $groups[$tagName][] = $databoxField->get_name();
        }

        return $groups;
    }

    /**
     * @param \databox_field[] $metaStructure
     * @param Metadata[] $metadataCollection
     * @return array
     */
    private function extractMetadataPerField($metaStructure, $metadataCollection)
    {
        $databoxFields = $this->groupDataboxFieldNamesPerTagName($metaStructure);

        $metadataPerField = [];

        foreach ($metadataCollection as $metadata) {
            $tagName = (string)$metadata->getTag()->getTagname();

            if (!isset($databoxFields[$tagName])) {
                continue;
            }

            foreach ($databoxFields[$tagName] as $fieldName) {
                if (!isset($metadataPerField[$fieldName])) {
                    $metadataPerField[$fieldName] = [];
                }

                $metadataPerField[$fieldName] = array_merge($metadataPerField[$fieldName], $metadata->getValue()->asArray());
            }
        }

        return $metadataPerField;
    }

    /**
     * @param \record_adapter $record
     * @param string $fieldName
     * @return void
     * @throws \Exception
     */
    private function deleteCaptionValues(\record_adapter $record, $fieldName)
    {
        $recordCaption = $record->get_caption();

        if ($recordCaption->has_field($fieldName)) {
            foreach ($recordCaption->get_field($fieldName)->get_values() as $value) {
                $value->delete();
            }
        }
    }

    /**
     * @param string[] $values
     * @param string $separator
     * @return array
     */
    private function getUniqueValues($values, $separator)
    {
        $tmpValues = [];

        foreach ($values as $value) {
            $tmpValues = array_merge($tmpValues, \caption_field::get_multi_values($value, $separator));
        }

        $values = array_unique($tmpValues);

        return $values;
    }
}
