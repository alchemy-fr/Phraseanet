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
use igorw;

class ElasticsearchRecordHydrator
{
    public static function hydrate(array $hit, $position)
    {
        $data = $hit['_source'];
        $highlight = isset($hit['highlight']) ? $hit['highlight'] : [];

        $record = new ElasticsearchRecord();

        $record->setPosition($position);
        $record->setBaseId(igorw\get_in($data, ['base_id'], 0));
        $record->setCollectionName(igorw\get_in($data, ['collection_name'], null));
        $record->setCollectionId(igorw\get_in($data, ['collection_id'], 0));
        $createdOn = igorw\get_in($data, ['created_on']);
        $record->setCreated($createdOn ? new \DateTime($createdOn) : $createdOn);
        $record->setDataboxId(igorw\get_in($data, ['databox_id'], 0));
        $record->setIsStory(igorw\get_in($data, ['type']) === 'story');
        $record->setMimeType(igorw\get_in($data, ['mime'], 'application/octet-stream'));
        $record->setOriginalName(igorw\get_in($data, ['original_name'], ''));
        $record->setRecordId(igorw\get_in($data, ['record_id'], 0));
        $record->setSha256(igorw\get_in($data, ['sha256'], ''));
        $record->setType(igorw\get_in($data, ['type'], 'unknown'));
        $updatedOn = igorw\get_in($data, ['updated_on']);
        $record->setUpdated($updatedOn ? new \DateTime($updatedOn) : $updatedOn);
        $record->setUuid(igorw\get_in($data, ['uuid'], ''));
        $record->setStatusBitField(igorw\get_in($data, ['flags_bitfield'], 0));
        $record->setTitles(new ArrayCollection((array) igorw\get_in($data, ['title'], [])));
        $record->setCaption(new ArrayCollection((array) igorw\get_in($data, ['caption'], [])));
        $record->setExif(new ArrayCollection((array) igorw\get_in($data, ['exif'], [])));
        $record->setSubdefs(new ArrayCollection((array) igorw\get_in($data, ['subdefs'], [])));
        $record->setFlags(new ArrayCollection((array) igorw\get_in($data, ['flags'], [])));
        $record->setHighlight(new ArrayCollection($highlight));

        return $record;
    }
}
