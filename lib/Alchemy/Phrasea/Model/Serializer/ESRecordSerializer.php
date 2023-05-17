<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Serializer;

use record_adapter;

class ESRecordSerializer extends AbstractSerializer
{
    public function serialize(record_adapter $record)
    {
        $caption = $business = $status = [];

        $technicalInformation = $record->get_technical_infos()->getValues();

        foreach ($record->get_caption()->get_fields(null, true) as $field) {
            $isDate = $field->get_databox_field()->get_type() === \databox_field::TYPE_DATE;
            $isBusiness = $field->get_databox_field()->isBusiness();

            $vi = $field->get_values();
            if ($field->is_multi()) {
                $values = [];
                foreach ($vi as $value) {
                    $values[] = $this->sanitizeSerializedValue($value->getValue());
                }
                $value = implode (' ' . $field->get_databox_field()->get_separator(false).' ', $values);
            } else {
                $value = $this->sanitizeSerializedValue(array_pop($vi)->getValue());
            }

            if ($isDate) {
                try {
                    $date = new \DateTime($value);
                    $value = $date->format(DATE_ATOM);
                } catch (\Exception $e) {
                    continue;
                }
            }

            if ($isBusiness) {
                $business[$field->get_databox_field()->get_name()] = $value;
            }

            $caption[$field->get_databox_field()->get_name()] = $value;
        }

        $i = 0;
        foreach (preg_split('//', strrev($record->getStatus()), -1, PREG_SPLIT_NO_EMPTY) as $val) {
            $status['status-'.$i] = (int) $val;
            $i++;
        }

        return [
            'databox_id'             => $record->getDataboxId(),
            'record_id'              => $record->getRecordId(),
            'collection_id'          => $record->getCollectionId(),
            'base_id'                => $record->getBaseId(),
            'mime_type'              => $record->getMimeType(),
            'title'                  => $record->get_title(['encode'=> record_adapter::ENCODE_NONE]),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->getUpdated()->format(DATE_ATOM),
            'created_on'             => $record->getCreated()->format(DATE_ATOM),
            'sha256'                 => $record->getSha256(),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->getType(),
            'type'                   => $record->isStory() ? 'story' : 'record',
            'uuid'                   => $record->getUuid(),
            'caption'                => $caption,
            'status'                 => $status,
            'caption-business'       => $business,
        ];
    }
}
