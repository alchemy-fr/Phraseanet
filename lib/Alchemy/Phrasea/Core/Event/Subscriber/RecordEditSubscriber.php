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

use Alchemy\Phrasea\Core\Event\Record\CollectionChangedEvent;
use Alchemy\Phrasea\Core\Event\Record\DeleteEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvent;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Core\Event\RecordEdit;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Metadata\Tag\TfEditdate;
use Alchemy\Phrasea\Model\RecordInterface;
use caption_Field_Value;
use Assert\Assertion;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RecordEditSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            PhraseaEvents::RECORD_EDIT         => 'onEdit',
            PhraseaEvents::RECORD_UPLOAD       => 'onEdit',
            RecordEvents::ROTATE               => 'onRecordChange',
            RecordEvents::COLLECTION_CHANGED   => 'onCollectionChanged',
            RecordEvents::SUBDEFINITION_CREATE => 'onSubdefinitionCreate',
        );
    }

    /**
     * @var callable
     */
    private $appboxLocator;

    public function __construct(callable $appboxLocator)
    {
        $this->appboxLocator = $appboxLocator;
    }

    public function onCollectionChanged(CollectionChangedEvent $event)
    {
        $recordAdapter = $this->convertToRecordAdapter($event->getRecord());
        $recordAdapter->clearStampCache();
    }

    public function onSubdefinitionCreate(SubdefinitionCreateEvent $event)
    {
        $recordAdapter = $this->convertToRecordAdapter($event->getRecord());
        $recordAdapter->rebuild_subdefs();
    }

    public function onEdit(RecordEdit $event)
    {
        static $into = false;
        static $editDateFields = [];    // array of fields "tfEditDate", by databox_id

        // prevent recursion
        if ($into) {
            return;
        }
        $into = true;

        $recordAdapter = $this->convertToRecordAdapter($event->getRecord());

        $databox = $recordAdapter->getDatabox();
        $sbas_id = $databox->get_sbas_id();
        if (!array_key_exists($sbas_id, $editDateFields)) {
            $editDateFields[$sbas_id] = [];

            $metaStructure = $databox->get_meta_structure();
            foreach ($metaStructure->get_elements() as $meta) {
                if ($meta->get_tag() instanceof TfEditdate) {
                    $editDateFields[$sbas_id][] = $meta;
                }
            }
        }

        if (!empty($editDateFields[$sbas_id])) {
            $this->updateRecord($recordAdapter, $editDateFields[$sbas_id], new \DateTime());
        }

        $into = false;
    }

    /**
     * @param RecordInterface $record
     * @return \databox
     */
    private function getRecordDatabox(RecordInterface $record)
    {
        return $this->getApplicationBox()->get_databox($record->getDataboxId());
    }

    /**
     * @return \appbox
     */
    private function getApplicationBox()
    {
        $callable = $this->appboxLocator;

        return $callable();
    }

    /**
     * @param \record_adapter $record
     * @param \databox_field[] $editFields
     * @param \DateTime $date
     * @throws \Exception_InvalidArgument
     */
    private function updateRecord(\record_adapter $record, array $editFields, \DateTime $date)
    {
        if (false === $record->isStory()) {
            // if a record is updated, every "parent" story is considered as updated too
            foreach ($record->get_grouping_parents() as $story) {
                $this->updateEditFields($story, $editFields, $date);
            }
        }
        $this->updateEditFields($record, $editFields, $date);
    }

    /**
     * @param \record_adapter $record
     * @param \databox_field[] $editFields
     * @param \DateTime $date
     * @throws \Exception_InvalidArgument
     */
    private function updateEditFields(\record_adapter $record, array $editFields, \DateTime $date)
    {
        static $updated = [];
        $id = $record->getId();

        // no need to update the same record twice (may happen is the record belongs to many stories)
        if(in_array($id, $updated)) {
            return;
        }

        foreach($editFields as $editField) {
            $metaId = null;
            try {
                $field = $record->get_caption()->get_field($editField->get_name(), true);
                if ($field) {
                    $values = $field->get_values();
                    /** @var caption_Field_Value $value */
                    $value = array_slice($values, -1)[0];   // if multivalued, only the last value will be updated
                    $metaId = $value->getId();
                }
            } catch (\Exception $e) {
                // field not found, $metaId==null -> value will be created
            }

            $record->set_metadatas(array(
                array(
                    'meta_struct_id' => $editField->get_id(),
                    'meta_id' => $metaId,
                    'value' => $date->format('Y-m-d H:i:s'),
                )
            ), true);
            // when edit record write meta also are dispatched
        }

        $record->clearStampCache();

        $updated[] = $id;
    }

    /**
     * @param RecordInterface $record
     * @return \record_adapter
     */
    private function convertToRecordAdapter(RecordInterface $record)
    {
        if ($record instanceof \record_adapter) {
            return $record;
        }

        $databox = $this->getRecordDatabox($record);

        $recordAdapter = $databox->getRecordRepository()->find($record->getRecordId());

        Assertion::isInstanceOf($recordAdapter, \record_adapter::class);

        return $recordAdapter;
    }

    public function onRecordChange(RecordEvent $recordEvent)
    {
        $record = $this->convertToRecordAdapter($recordEvent->getRecord());

        $record->touch();
    }
}
