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

use Assert\Assertion;

class StoryView
{
    use SubdefsAware;
    use CaptionAware;

    /**
     * @var \record_adapter
     */
    private $story;
    /**
     * @var RecordView[]
     */
    private $children = [];

    /**
     * @param \record_adapter $story
     */
    public function __construct(\record_adapter $story)
    {

        $this->story = $story;
    }

    /**
     * @return \record_adapter
     */
    public function getStory()
    {
        return $this->story;
    }

    /**
     * @param RecordView[] $children
     */
    public function setChildren($children)
    {
        Assertion::allIsInstanceOf($children, RecordView::class);

        $this->children = $children instanceof \Traversable ? iterator_to_array($children, false) : array_values($children);
    }

    /**
     * @return RecordView[]
     */
    public function getChildren()
    {
        return $this->children;
    }
}
