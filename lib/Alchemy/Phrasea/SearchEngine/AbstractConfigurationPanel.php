<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine;

abstract class AbstractConfigurationPanel implements ConfigurationPanelInterface
{
    /**
     * Return the path to the file where the configuration is saved
     *
     * @return string The path to the file
     */
    public function getConfigPathFile()
    {
        return __DIR__ . '/../../../../config/'.$this->getName().'.json';
    }

    /**
     * {@inheritdoc}
     */
    public function getAvailableDateFields(array $databoxes)
    {
        $date_fields = array();

        foreach ($databoxes as $databox) {
            foreach ($databox->get_meta_structure() as $field) {
                if ($field->get_type() !== \databox_field::TYPE_DATE) {
                    continue;
                }

                $date_fields[] = $field->get_name();
            }
        }

        return $date_fields;
    }
}
