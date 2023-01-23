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

use Alchemy\Phrasea\Core\PhraseaTokens;
use PDO;

class ScheduledFetcherDelegate implements FetcherDelegateInterface
{
    public function buildWhereClause()
    {
        return '(r.jeton & :to_index) > 0 AND (jeton & :indexing) = 0';
    }

    public function getParameters()
    {
        return array(
            ':to_index' => PhraseaTokens::TO_INDEX,
            ':indexing' => PhraseaTokens::INDEXING
        );
    }

    public function getParametersTypes()
    {
        return array(
            ':to_index' => PDO::PARAM_INT,
            ':indexing' => PDO::PARAM_INT
        );
    }
}
