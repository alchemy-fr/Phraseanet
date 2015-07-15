<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox\Record;

interface RecordRepository
{
    /**
     * @param mixed $record_id
     * @return \record_adapter|null
     */
    public function find($record_id);

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
}
