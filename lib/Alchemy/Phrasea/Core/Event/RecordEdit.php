<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\RecordsRequest;
use Symfony\Component\EventDispatcher\Event as SfEvent;

class RecordEdit extends SfEvent
{
    private $records = array();
    private $collections = array();
    private $databoxes = array();

    public function __construct($data)
    {
        if ($data instanceof RecordsRequest) {
            $this->records = $data->toArray();
            $this->collections = $data->collections();
            $this->databoxes = $data->databoxes();
        } elseif ($data instanceof \record_adapter) {
            $this->records[] = $data;
            $this->collections[] = $data->get_collection();
            $this->databoxes[] = $data->get_databox();
        }

    }

    public function getRecords()
    {
        return $this->records;
    }

    public function getCollections()
    {
        return $this->collections;
    }

    public function getDataboxes()
    {
        return $this->databoxes;
    }
}
