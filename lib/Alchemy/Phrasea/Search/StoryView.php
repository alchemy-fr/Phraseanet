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

/**
 * used as a (temporary / specific) view of a story
 * WARNING : the children DO NOT NECESSARILY reflect the whole content of a story
 *           children may contain only a subset :
 *                 - visible for a specific user
 *                 - paginated
 *
 * Class StoryView
 * @package Alchemy\Phrasea\Search
 */

class StoryView extends RecordView
{
    use SubdefsAware;
    use CaptionAware;

    /**
     * @var RecordView[]
     * may be a subset of all children (only visibles for a user and/or paginated)
     */
    private $children = [];

    /**
     * @var mixed[]
     * "personal use" data to be stored/retreived for a specific usage
     * e.g. api V3 will store pagination infos to be rendered on output
     */
    private $_data = [];

    /**
     * @param \record_adapter $story
     */
    public function __construct(\record_adapter $story)
    {
        parent::__construct($story);
    }

    /**
     * @return \record_adapter
     */
    public function getStory()
    {
        return $this->record;
    }

    /**
     * @param RecordView[] $children
     * @return self
     */
    public function setChildren($children)
    {
        Assertion::allIsInstanceOf($children, RecordView::class);

        $this->children = $children instanceof \Traversable ? iterator_to_array($children, false) : array_values($children);

        return $this;
    }

    /**
     * @return RecordView[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * set a "personal usage" data
     *
     * @param string $k
     * @param mixed $v
     * @return self
     */
    public function setData($k, $v)
    {
        $this->_data[$k] = $v;

        return $this;
    }

    /**
     * get a "personal usage" data (null if not found)
     *
     * @param string $k
     * @return mixed|null
     */
    public function getData($k)
    {
        return isset($this->_data[$k]) ? $this->_data[$k] : null;
    }
}
