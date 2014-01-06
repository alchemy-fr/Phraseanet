<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\RSS;

class Image implements ImageInterface
{
    /**
     *
     * @var string
     */
    protected $url;

    /**
     *
     * @var string
     */
    protected $title;

    /**
     *
     * @var string
     */
    protected $link;

    /**
     *
     * @var string
     */
    protected $description;

    /**
     *
     * @var int
     */
    protected $width;

    /**
     *
     * @var int
     */
    protected $height;

    /**
     *
     * @param  string       $url
     * @param  string       $title
     * @param  string       $link
     * @return FeedRSSImage
     */
    public function __construct($url, $title, $link)
    {
        $this->url = $url;
        $this->title = $title;
        $this->link = $link;

        return $this;
    }

    /**
     *
     * @param  type         $description
     * @return FeedRSSImage
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     *
     * @param  int          $width
     * @return FeedRSSImage
     */
    public function setWidth($width)
    {
        $this->width = (int) $width;

        return $this;
    }

    /**
     *
     * @param  int          $height
     * @return FeedRSSImage
     */
    public function setHeight($height)
    {
        $this->height = (int) $height;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     *
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     *
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }
}
