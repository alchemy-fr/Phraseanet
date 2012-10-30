<?php

namespace Alchemy\Phrasea\SearchEngine;

abstract class AbstractConfigurationPanel implements ConfigurationPanelInterface
{
    public function getConfigPathFile()
    {
        return __DIR__ . '/../../../../config/'.$this->getName().'.json';
    }

    public function getAvailableDateFields(array $databoxes)
    {
        $date_fields = array();
        
        foreach ($databoxes as $databox) {
            foreach($databox->get_meta_structure() as $field) {
                if ($field->get_type() !== \databox_field::TYPE_DATE) {
                    continue;
                }
                
                $date_fields[] = $field->get_name();
            }
        }
        
        return $date_fields;
    }
}
