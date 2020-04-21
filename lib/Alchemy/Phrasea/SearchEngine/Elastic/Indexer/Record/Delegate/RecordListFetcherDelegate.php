<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\SearchEngine\Elastic\Indexer\Record\Delegate;

use Doctrine\DBAL\Connection;

class RecordListFetcherDelegate implements FetcherDelegateInterface
{
    private $records;

    public function __construct(array $records)
    {
        $this->records = $records;
    }

    public function buildWhereClause()
    {
        return 'record_id IN (:record_identifiers)';
    }

    public function getParameters()
    {
        return array(':record_identifiers' => $this->getRecordIdentifiers());
    }

    public function getParametersTypes()
    {
        return array(':record_identifiers' => Connection::PARAM_INT_ARRAY);
    }

    private function getRecordIdentifiers()
    {
        $identifiers = array();
        foreach ($this->records as $record) {
            $identifiers[] = $record->getRecordId();
        }

        return $identifiers;
    }
}
