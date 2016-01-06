<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox\Record;

interface RecordRepository
{
    /**
     * @param mixed    $record_id
     * @param null|int $number
     * @return null|\record_adapter
     */
    public function find($record_id, $number = null);

    /**
     * @param string $sha256
     * @return \record_adapter[]
     */
    public function findBySha256($sha256);

    /**
     * @param string $uuid
     * @return \record_adapter[]
     */
    public function findByUuid($uuid);

    /**
     * @param array $recordIds
     * @return \record_adapter[]
     */
    public function findByRecordIds(array $recordIds);
}
