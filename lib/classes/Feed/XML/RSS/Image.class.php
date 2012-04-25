<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Feeds
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Feed_XML_RSS_Image implements Feed_XML_RSS_ImageInterface
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
     * @param string $url
     * @param string $title
     * @param string $link
     * @return Feed_XML_RSS_Image
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
     * @param type $description
     * @return Feed_XML_RSS_Image
     */
    public function set_description($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     *
     * @param int $width
     * @return Feed_XML_RSS_Image
     */
    public function set_width($width)
    {
        $this->width = (int) $width;

        return $this;
    }

    /**
     *
     * @param int $height
     * @return Feed_XML_RSS_Image
     */
    public function set_height($height)
    {
        $this->height = (int) $height;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return $this->url;
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     *
     * @return string
     */
    public function get_link()
    {
        return $this->link;
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     *
     * @return int
     */
    public function get_width()
    {
        return $this->width;
    }

    /**
     *
     * @return int
     */
    public function get_height()
    {
        return $this->height;
    }
}
