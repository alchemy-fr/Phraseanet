<?php

namespace Alchemy\Phrasea\WorkerManager\Worker\RecordsActionsWorker;

use Alchemy\Phrasea\Application as PhraseaApplication;
use collection;
use databox;
use databox_field;

class GetByIdOrNameHelper
{
    /**
     * @var PhraseaApplication
     */
    private $app;

    private $databoxes = [];

    public function __construct(PhraseaApplication $app)
    {
        $this->app = $app;

        // allow to access databox/collections by id or name
        foreach ($app->getDataboxes() as $databox) {
            $bid = (string)($databox->get_sbas_id());
            $this->databoxes[$bid] = [
                'db' => $databox,
                'collections' => [],
                'fields' => []
            ];
            $this->databoxes[$databox->get_dbname()] = &$this->databoxes[$bid];

            foreach ($databox->get_collections() as $coll) {
                $cid = (string)($coll->get_coll_id());
                $this->databoxes[$bid]['collections'][$cid] = $coll;
                $this->databoxes[$bid]['collections'][$coll->get_name()] = &$this->databoxes[$bid]['collections'][$cid];
            }

            foreach($databox->get_meta_structure() as $field) {
                $fid = $field->get_id();
                $this->databoxes[$bid]['fields'][$fid] = $field;
                $this->databoxes[$bid]['fields'][$field->get_name()] = &$this->databoxes[$bid]['fields'][$fid];
            }
        }
    }

    /**
     * @param int|string $dbIdOrName
     * @return databox|null
     */
    public function getDatabox($dbIdOrName)
    {
        $dbIdOrName = (string)$dbIdOrName;
        if(array_key_exists($dbIdOrName, $this->databoxes)) {
            return $this->databoxes[$dbIdOrName]['db'];
        }
        return null;
    }

    /**
     * @param databox|int|string $dbIdOrName
     * @param int|string $collIdOrName
     * @return collection|null
     */
    public function getCollection($dbIdOrName, $collIdOrName)
    {
        if($dbIdOrName instanceof databox) {
            $dbIdOrName = $dbIdOrName->get_sbas_id();
        }
        $dbIdOrName = (string)$dbIdOrName;
        if (array_key_exists($dbIdOrName, $this->databoxes)) {
            $collIdOrName = (string)$collIdOrName;
            if (array_key_exists($collIdOrName, $this->databoxes[$dbIdOrName]['collections'])) {
                return $this->databoxes[$dbIdOrName]['collections'][$collIdOrName];
            }
        }
        return null;
    }

    /**
     * @param databox|int|string $dbIdOrName
     * @param int|string $fieldIdOrName
     * @return databox_field|null
     */
    public function getField($dbIdOrName, $fieldIdOrName)
    {
        if($dbIdOrName instanceof databox) {
            $dbIdOrName = $dbIdOrName->get_sbas_id();
        }
        $dbIdOrName = (string)$dbIdOrName;
        if (array_key_exists($dbIdOrName, $this->databoxes)) {
            $fieldIdOrName = (string)$fieldIdOrName;
            if (array_key_exists($fieldIdOrName, $this->databoxes[$dbIdOrName]['fields'])) {
                return $this->databoxes[$dbIdOrName]['fields'][$fieldIdOrName];
            }
        }
        return null;
    }
}
