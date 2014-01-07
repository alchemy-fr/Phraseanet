<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Serializer;

class ESRecordSerializer extends AbstractSerializer
{
    public function serialize(\record_adapter $record)
    {
        $technicalInformation = $caption = $business = $status = [];

        foreach ($record->get_technical_infos() as $name => $value) {
            $technicalInformation[$name] = $value;
        }

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
        foreach (preg_split('//', strrev($record->get_status()), -1, PREG_SPLIT_NO_EMPTY) as $val) {
            $status['status-'.$i] = (int) $val;
            $i++;
        }

        return [
            'databox_id'             => $record->get_sbas_id(),
            'record_id'              => $record->get_record_id(),
            'collection_id'          => $record->get_collection()->get_coll_id(),
            'base_id'                => $record->get_base_id(),
            'mime_type'              => $record->get_mime(),
            'title'                  => $record->get_title(),
            'original_name'          => $record->get_original_name(),
            'updated_on'             => $record->get_modification_date()->format(DATE_ATOM),
            'created_on'             => $record->get_creation_date()->format(DATE_ATOM),
            'sha256'                 => $record->get_sha256(),
            'technical_informations' => $technicalInformation,
            'phrasea_type'           => $record->get_type(),
            'type'                   => $record->is_grouping() ? 'story' : 'record',
            'uuid'                   => $record->get_uuid(),
            'caption'                => $caption,
            'status'                 => $status,
            'caption-business'       => $business,
        ];
    }
}
