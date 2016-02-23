<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Subscriber;

use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Metadata\Tag\TfEditdate;
use Alchemy\Phrasea\Model\RecordInterface;
use Assert\Assertion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordEditSubscriber implements EventSubscriberInterface
{
    /**
     * @var \appbox
     */
    private $appbox;

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    public function onEdit(RecordEdit $event)
    {
        $record = $event->getRecord();

        $databox = $this->appbox->get_databox($record->getDataboxId());

        $metaStructure = $databox->get_meta_structure();
        $editDateField = false;
        foreach ($metaStructure->get_elements() as $meta) {
            if ($meta->get_tag() instanceof TfEditdate) {
                $editDateField = $meta;
            }
        }

        if ($editDateField instanceof \databox_field) {
            $this->updateRecord($this->convertToRecordAdapter($databox, $record), $editDateField);
        }
    }

    /**
     * @param \databox $databox
     * @param RecordInterface $record
     * @return \record_adapter
     */
    private function convertToRecordAdapter(\databox $databox, RecordInterface $record)
    {
        if ($record instanceof \record_adapter) {
            return $record;
        }

        $recordAdapter = $databox->getRecordRepository()->find($record->getRecordId());

        Assertion::isInstanceOf($recordAdapter, \record_adapter::class);

        return $recordAdapter;
    }

    private function updateRecord(\record_adapter $record, $field)
    {
        if (false === $record->isStory()) {
            foreach ($record->get_grouping_parents() as $story) {
                $this->updateEditField($story, $field);
            }
        }
        $this->updateEditField($record, $field);
    }


    private function updateEditField(\record_adapter $record, \databox_field $editField)
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
