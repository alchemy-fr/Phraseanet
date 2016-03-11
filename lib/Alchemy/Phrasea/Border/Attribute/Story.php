<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Attribute;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Phraseanet Border Story Attribute
 *
 * This attribute is used to store a destination story for a file, prior to
 * their record creation
 */
class Story implements AttributeInterface
{
    protected $story;

    /**
     * Constructor
     *
     * @param \record_adapter $story The destination story
     */
    public function __construct(\record_adapter $story)
    {
        if (!$story->isStory()) {
            throw new \InvalidArgumentException('Unable to fetch a story from string');
        }

        $this->story = $story;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->story = null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME_STORY;
    }

    /**
     * {@inheritdoc}
     *
     * @return \record_adapter The story
     */
    public function getValue()
    {
        return $this->story;
    }

    /**
     * {@inheritdoc}
     */
    public function asString()
    {
        return $this->story->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return Story
     */
    public static function loadFromString(Application $app, $string)
    {
        $ids = explode('_', $string);

        try {
            $story = new \record_adapter($app, $ids[0], $ids[1]);
        } catch (NotFoundHttpException $e) {
            throw new \InvalidArgumentException('Unable to fetch a story from string');
        }

        if (!$story->isStory()) {
            throw new \InvalidArgumentException('Unable to fetch a story from string');
        }

        return new static($story);
    }
}
