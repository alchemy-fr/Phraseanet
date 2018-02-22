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

class RecordIdListFetcherDelegate implements FetcherDelegateInterface
{
    private $record_ids;

    public function __construct(array $record_ids)
    {
        $this->record_ids = $record_ids;
    }

    public function buildWhereClause()
    {
        return 'r.record_id IN (:record_identifiers)';
    }

    public function getParameters()
    {
        return array(':record_identifiers' => $this->record_ids);
    }

    public function getParametersTypes()
    {
        return array(':record_identifiers' => Connection::PARAM_INT_ARRAY);
    }
}