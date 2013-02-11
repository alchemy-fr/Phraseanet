<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * MetaFieldsBad is a collection of MetaField attributes indexed by field names.
 *
 * It's used to handle field-oriented metadata before a record insertion.
 */
class MetaFieldsBag extends ArrayCollection implements MetaBagInterface
{
    /**
     * {@inheritdoc}
     */
    public function toMetadataArray(\databox_descriptionStructure $metadatasStructure)
    {
        $metas = array();
        $unicode = new \unicode();

        foreach ($metadatasStructure as $databox_field) {
            if ($this->containsKey($databox_field->get_name())) {
                if ($databox_field->is_multi()) {

                    $values = $this->get($databox_field->get_name())->getValue();

                    $tmp = array();

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

                        $metas[] = array(
                            'meta_struct_id' => $databox_field->get_id(),
                            'value'          => $value,
                            'meta_id'        => null
                        );
                    }
                } else {

                    $values = $this->get($databox_field->get_name())->getValue();
                    $value = array_shift($values);

                    $value = $unicode->substituteCtrlCharacters($value, ' ');
                    $value = $unicode->toUTF8($value);
                    if ($databox_field->get_type() == 'date') {
                        $value = $unicode->parseDate($value);
                    }

                    $metas[] = array(
                        'meta_struct_id' => $databox_field->get_id(),
                        'value'          => $value,
                        'meta_id'        => null
                    );
                }
            }
        }

        return $metas;
    }
}
