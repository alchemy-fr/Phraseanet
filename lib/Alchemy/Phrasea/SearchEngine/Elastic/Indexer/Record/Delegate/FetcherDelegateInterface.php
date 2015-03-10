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

interface FetcherDelegateInterface
{
    public function buildWhereClause();
    public function getParameters();
    public function getParametersTypes();
}
