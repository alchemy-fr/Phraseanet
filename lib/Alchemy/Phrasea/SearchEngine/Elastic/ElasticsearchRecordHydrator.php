<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic;

use Alchemy\Phrasea\Model\Entities\ElasticsearchRecord;
use Doctrine\Common\Collections\ArrayCollection;

class ElasticsearchRecordHydrator
{
    public static function hydrate(array $data, $position)
    {
        $record = new ElasticsearchRecord();

        $record->setPosition($position);
        $record->setBaseId(isset($data['base_id']) ? $data['base_id'] : 0);
        $record->setCollectionId(isset($data['collection_id']) ? $data['collection_id'] : 0);
        $record->setCreated(isset($data['created_on']) ? new \DateTime($data['created_on']) : null);
        $record->setDataboxId(isset($data['databox_id']) ? $data['databox_id'] : 0);
        $record->setIsStory(isset($data['type']) ? $data['type'] === 'story' : null);
        $record->setMimeType(isset($data['mime']) ? $data['mime'] : 'application/octet-stream');
        $record->setOriginalName(isset($data['original_name']) ? $data['original_name'] : '');
        $record->setRecordId(isset($data['record_id']) ? $data['record_id'] : 0);
        $record->setSha256(isset($data['sha256']) ? $data['sha256'] : '');
        $record->setType(isset($data['record_type']) ? $data['record_type'] : 'unknown');
        $record->setUpdated(isset($data['updated_on']) ? new \DateTime($data['updated_on']) : null);
        $record->setUuid(isset($data['uuid']) ? $data['uuid'] : '');
        $record->setStatus(isset($data['bin_status']) ? $data['bin_status'] : str_repeat("0", 32));
        $record->setTitles(new ArrayCollection(isset($data['title']) ? (array) $data['title'] : []));
        $record->setCaption(new ArrayCollection(isset($data['caption']) ? (array) $data['caption'] : []));
        $record->setExif(new ArrayCollection(isset($data['exif']) ? (array) $data['exif'] : []));
        $record->setSubdefs(new ArrayCollection(isset($data['subdefs']) ? (array) $data['subdefs'] : []));

        return $record;
    }
}