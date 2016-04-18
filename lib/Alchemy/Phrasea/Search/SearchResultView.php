<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Alchemy\Phrasea\SearchEngine\SearchEngineResult;

class SearchResultView
{
    /**
     * @var SearchEngineResult
     */
    private $result;

    public function __construct(SearchEngineResult $result)
    {
        $this->result = $result;
    }

    /**
     * @return SearchEngineResult
     */
    public function getResult()
    {
        return $this->result;
    }
}
