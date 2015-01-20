<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use PHPExiftool\Driver\Metadata\Metadata;

class PhraseanetMetadataSetter
{
    public function replaceMetadata($metadataCollection, \record_adapter $record)
    {
        $metadatas = array();

        $tagnameToFieldnameMapping = array();
        $arrayStructure = iterator_to_array($record->get_databox()->get_meta_structure());

        array_walk($arrayStructure, function ($databoxField) use (&$tagnameToFieldnameMapping) {
            $tagname = $databoxField->get_tag()->getTagname();
            $tagnameToFieldnameMapping[$tagname][] = $databoxField->get_name();
        });

        array_walk($metadataCollection, function (Metadata $metadata) use (&$metadatas, $tagnameToFieldnameMapping) {
            $tagname = $metadata->getTag()->getTagname();

            if (!isset($tagnameToFieldnameMapping[$tagname])) {
                return;
            }

            foreach ($tagnameToFieldnameMapping[$tagname] as $fieldname) {
                if ( ! isset($metadatas[$fieldname])) {
                    $metadatas[$fieldname] = array();
                }
                $metadatas[$fieldname] = array_merge($metadatas[$fieldname], $metadata->getValue()->asArray());
            }
        });

        $metas = array();

        array_walk($arrayStructure, function (\databox_field $field) use (&$metas, $metadatas, $record) {
            $fieldname = $field->get_name();

            if (!isset($metadatas[$fieldname])) {
                return;
            }

            $values = $metadatas[$fieldname];

            if ($record->get_caption()->has_field($fieldname)) {
                foreach ($record->get_caption()->get_field($fieldname)->get_values() as $value) {
                    $value->delete();
                }
            }

            if ($field->is_multi()) {
                $tmpValues = array();
                foreach ($values as $value) {
                    $tmpValues = array_merge($tmpValues, \caption_field::get_multi_values($value, $field->get_separator()));
                }

                $values = array_unique($tmpValues);

                foreach ($values as $value) {
                    if (trim($value) === '') {
                        continue;
                    }
                    $metas[] = array(
                        'meta_struct_id' => $field->get_id(),
                        'meta_id'        => null,
                        'value'          => $value,
                    );
                }
            } else {
                $value = array_pop($values);
                if (trim($value) === '') {
                    return;
                }

                $metas[] = array(
                    'meta_struct_id' => $field->get_id(),
                    'meta_id'        => null,
                    'value'          => $value,
                );
            }
        });

        if (count($metas) > 0) {
            $record->set_metadatas($metas, true);
        }
    }
}
