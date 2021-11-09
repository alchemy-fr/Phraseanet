<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Databox\DataboxRepository;
use Alchemy\Phrasea\Metadata\Tag\NoSource;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use DateTime;
use PHPExiftool\Driver\Metadata\Metadata;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PhraseanetMetadataSetter
{
    /**
     * @var DataboxRepository
     */
    private $repository;

    private $dispatcher;

    public function __construct(DataboxRepository $repository, EventDispatcherInterface $dispatcher)
    {
        $this->repository = $repository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Metadata[] $metadataCollection
     * @param \record_adapter $record
     * @throws \Exception_InvalidArgument
     */
    public function replaceMetadata($metadataCollection, \record_adapter $record)
    {
        $metaStructure = $this->repository->find($record->getDataboxId())->get_meta_structure()->get_elements();

        $metadataPerField = $this->extractMetadataPerField($metaStructure, $metadataCollection);

        $metadataInRecordFormat = [];

        foreach ($metaStructure as $field) {
            $fieldName = $field->get_name();

            if (!isset($metadataPerField[$fieldName])) {
                continue;
            }

            $this->deleteCaptionValues($record, $fieldName);

            $values = $metadataPerField[$fieldName];

            if ($field->is_multi()) {
                $values = $this->getUniqueValues($values, $field->get_separator());
            }

            $data = [
                'meta_struct_id' => $field->get_id(),
                'meta_id' => null,
            ];

            foreach ($values as $value) {
                if (trim($value) === '') {
                    continue;
                }

                if ($field->get_type() == 'date') {
                    try {
                        $dateTime = new DateTime($value);
                        $value = $dateTime->format('Y/m/d H:i:s');
                    } catch (\Exception $e) {
                        // $value unchanged
                    }
                }

                $data['value'] = $value;
                $metadataInRecordFormat[] = $data;
            }
        }

        if (! empty($metadataInRecordFormat)) {
            /*
             * todo : tryout to write meta AFTER meta was written in db by set_metadatas()
             * how : uncomment section
             *
             */
            /*
            $this->dispatcher->addListener(
                RecordEvents::METADATA_CHANGED,
                function () use ($record) {
                    // order to write meta in file
                    // this event will add a msg to the writemeta q
                    $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
                        new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));
                },
                0
            );
            */
            /*
             * end of section to uncomment
             */

            $record->set_metadatas($metadataInRecordFormat, true);  // will send METADATA_CHANGED when done

            /*
             * todo : tryout to write meta AFTER meta was written in db by set_metadatas()
             * how : comment section
             *
             */
            // order to write meta in file

            $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
                new RecordsWriteMetaEvent([$record->getRecordId()], $record->getDataboxId()));
            /*
             * end of section to comment
             */
        }
    }

    /**
     * @param \databox_field[] $metaStructure
     * @return array<string,array<string>>
     */
    private function groupDataboxFieldNamesPerTagName($metaStructure)
    {
        $groups = [];

        foreach ($metaStructure as $databoxField) {
            $tagName = $this->extractTagNameFromTag($databoxField->get_tag());

            if (!isset($groups[$tagName])) {
                $groups[$tagName] = [];
            }

            $groups[$tagName][] = $databoxField->get_name();
        }

        return $groups;
    }

    /**
     * @param \databox_field[] $metaStructure
     * @param Metadata[] $metadataCollection
     * @return array
     */
    private function extractMetadataPerField($metaStructure, $metadataCollection)
    {
        $databoxFields = $this->groupDataboxFieldNamesPerTagName($metaStructure);

        $metadataPerField = [];

        foreach ($metadataCollection as $metadata) {
            $tagName = $this->extractTagNameFromTag($metadata->getTag());

            if (!isset($databoxFields[$tagName])) {
                continue;
            }

            foreach ($databoxFields[$tagName] as $fieldName) {
                if (!isset($metadataPerField[$fieldName])) {
                    $metadataPerField[$fieldName] = [];
                }
                if(in_array($tagName, File::$xmpTag)){
                    $metadataPerField[$fieldName] = array_merge($metadataPerField[$fieldName], (array) File::sanitizeXmpUuid($metadata->getValue()->asString()));
                }else{
                    $metadataPerField[$fieldName] = array_merge($metadataPerField[$fieldName], $metadata->getValue()->asArray());
                }
            }
        }

        return $metadataPerField;
    }

    /**
     * @param \record_adapter $record
     * @param string $fieldName
     * @return void
     * @throws \Exception
     */
    private function deleteCaptionValues(\record_adapter $record, $fieldName)
    {
        $recordCaption = $record->get_caption();

        if ($recordCaption->has_field($fieldName)) {
            foreach ($recordCaption->get_field($fieldName)->get_values() as $value) {
                $value->delete();
            }
        }
    }

    /**
     * @param string[] $values
     * @param string $separator
     * @return array
     */
    private function getUniqueValues($values, $separator)
    {
        $tmpValues = [];

        foreach ($values as $value) {
            $tmpValues = array_merge($tmpValues, \caption_field::get_multi_values($value, $separator));
        }

        $values = array_unique($tmpValues);

        return $values;
    }

    /**
     * @param object $tag TagInterface
     * @return string
     */
    private function extractTagNameFromTag($tag)
    {
        return $tag instanceof NoSource ? $tag->getFieldName() : $tag->getTagname();
    }
}
