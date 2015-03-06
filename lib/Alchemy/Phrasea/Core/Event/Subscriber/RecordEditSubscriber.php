<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Alchemy\Phrasea\Metadata\Tag\TfEditdate;


class RecordEditSubscriber implements EventSubscriberInterface
{
    public function onEdit(RecordEdit $event)
    {
        $records = $event->getRecords();
        $databoxes = $event->getDataboxes();
        $databox = array_pop($databoxes);

        $metaStructure = $databox->get_meta_structure();
        $editDateField = false;
        foreach ($metaStructure->get_elements() as $meta) {
            if ($meta->get_tag() instanceof TfEditdate) {
                $editDateField = $meta;
            }
        }

        if ($editDateField instanceof \databox_field) {
            foreach ($records as $record) {
                $this->updateRecord($record, $editDateField);
            }
        }
    }

    private function updateRecord($record, $field)
    {
        if (false === $record->is_grouping()) {
            foreach ($record->get_grouping_parents() as $story) {
                $this->updateEditField($story, $field);
            }
        }
        $this->updateEditField($record, $field);
    }


    private function updateEditField($record, $editField)
    {
        $fields = $record->get_caption()->get_fields(array($editField->get_name()), true);
        $field = array_pop($fields);

        $metaId = null;

        if ($field && !$field->is_multi()) {
            $values = $field->get_values();
            $metaId = array_pop($values)->getId();
        }

        $date = new \DateTime();
        $record->set_metadatas(array(
            array(
                'meta_struct_id' => $editField->get_id(),
                'meta_id'        => $metaId,
                'value'          => $date->format('Y-m-d H:i:s'),
            )
        ), true);
    }

    public static function getSubscribedEvents()
    {
        return array(
            PhraseaEvents::RECORD_EDIT => 'onEdit',
            PhraseaEvents::RECORD_UPLOAD => 'onEdit',
        );
    }
}
