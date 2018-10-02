<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * MetaFieldsBad is a collection of Metadata attributes indexed by metadata
 * source.
 *
 * It's used to handle source-oriented metadata before a record insertion.
 */
class MetadataBag extends ArrayCollection implements MetaBagInterface
{
    /**
     * {@inheritdoc}
     */
    public function toMetadataArray(\databox_descriptionStructure $metadatasStructure)
    {
        $metas = [];
        $unicode = new \unicode();

        foreach ($metadatasStructure as $databox_field) {

            if ('' === $databox_field->get_tag()->getTagname()) {
                // skipping fields without sources
                continue;
            }

            if ($this->containsKey($databox_field->get_tag()->getTagname())) {

                if ($databox_field->is_multi()) {

                    $values = $this->get($databox_field->get_tag()->getTagname())->getValue()->asArray();

                    $tmp = [];

                    foreach ($values as $value) {
                        foreach (\caption_field::get_multi_values($value, $databox_field->get_separator()) as $v) {
                            $tmp[] = $v;
                        }
                    }

                    $values = array_unique($tmp);

                    foreach ($values as $value) {

                        $value = $unicode->substituteCtrlCharacters($value, ' ');
                        $value = $unicode->toUTF8($value);
                        if ($databox_field->get_type() == 'date') {
                            $value = $unicode->parseDate($value);
                        }

                        $metas[] = [
                            'meta_struct_id' => $databox_field->get_id(),
                            'value'          => $value,
                            'meta_id'        => null
                        ];
                    }
                } else {
                    $value = $this->get($databox_field->get_tag()->getTagname())->getValue()->asString();

                    $value = $unicode->substituteCtrlCharacters($value, ' ');
                    $value = $unicode->toUTF8($value);
                    if ($databox_field->get_type() == 'date') {
                        $value = $unicode->parseDate($value);
                    }

                    $metas[] = [
                        'meta_struct_id' => $databox_field->get_id(),
                        'value'          => $value,
                        'meta_id'        => null
                    ];
                }
            }
        }

        unset($unicode);

        return $metas;
    }
}
