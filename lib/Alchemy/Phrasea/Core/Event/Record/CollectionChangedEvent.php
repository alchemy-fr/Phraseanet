<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Event\Record;

class CollectionChangedEvent extends RecordEvent
{
    /** @var array */
    private $beforeCollection;

    /** @var array */
    private $afterCollection;

    public function __construct(\record_adapter $record, \collection $beforeCol, \collection $afterCol)
    {
        parent::__construct($record);

        $this->beforeCollection = [
            'collection_name'   => $beforeCol->get_name(),
            'base_id'           => $beforeCol->get_base_id()
        ];

        $this->afterCollection = [
            'collection_name'   => $afterCol->get_name(),
            'base_id'           => $afterCol->get_base_id()
        ];
    }

    /**
     * @return array
     */
    public function getBeforeCollection()
    {
        return $this->beforeCollection;
    }

    /**
     * @return array
     */
    public function getAfterCollection()
    {
        return $this->afterCollection;
    }
}
