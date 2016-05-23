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
use Assert\Assertion;

class SearchResultView
{
    /**
     * @var SearchEngineResult
     */
    private $result;

    /**
     * @var StoryView[]
     */
    private $stories = [];

    /**
     * @var RecordView[]
     */
    private $records = [];

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

    /**
     * @param StoryView[] $stories
     * @return void
     */
    public function setStories($stories)
    {
        Assertion::allIsInstanceOf($stories, StoryView::class);

        $this->stories = $stories instanceof \Traversable ? iterator_to_array($stories, false) : array_values($stories);
    }

    /**
     * @return StoryView[]
     */
    public function getStories()
    {
        return $this->stories;
    }

    /**
     * @param RecordView[] $records
     * @return void
     */
    public function setRecords($records)
    {
        Assertion::allIsInstanceOf($records, RecordView::class);

        $this->records = $records instanceof \Traversable ? iterator_to_array($records, false) : array_values($records);
    }

    /**
     * @return RecordView[]
     */
    public function getRecords()
    {
        return $this->records;
    }
}
