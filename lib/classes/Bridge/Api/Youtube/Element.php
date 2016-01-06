<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Youtube_Element implements Bridge_Api_ElementInterface
{
    /**
     *
     * @var Zend_Gdata_App_Entry
     */
    protected $entry;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @param  Zend_Gdata_App_Entry       $entry
     * @param  string                     $type
     * @return Bridge_Api_Youtube_Element
     */
    public function __construct(Zend_Gdata_App_Entry $entry, $type)
    {
        $this->entry = $entry;
        $this->type = $type;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_id()
    {
        return $this->entry->getVideoId();
    }

    /**
     * Return the thumbnail of the element
     * Available size : 120*90;480*360;
     *
     * @return string
     */
    public function get_thumbnail()
    {
        $video_thumbnails = $this->entry->getVideoThumbnails();

        foreach ($video_thumbnails as $thumb) {
            if (120 == $thumb['width'] && 90 == $thumb['height']) {
                return $thumb['url'];
            }
        }
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return $this->entry->getVideoWatchPageUrl();
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->entry->getVideoTitle();
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->entry->getVideoDescription();
    }

    /**
     *
     * @return DateTime
     */
    public function get_updated_on()
    {
        return new DateTime($this->entry->getUpdated()->getText());
    }

    /**
     *
     * @return string
     */
    public function get_category()
    {
        return $this->entry->getVideoCategory();
    }

    /**
     *
     * @return string
     */
    public function get_duration()
    {
        return p4string::format_seconds($this->entry->getVideoDuration());
    }

    /**
     *
     * @return int
     */
    public function get_view_count()
    {
        return (int) $this->entry->getVideoViewCount();
    }

    /**
     *
     * @return int
     */
    public function get_rating()
    {
        $rating_info = $this->entry->getVideoRatingInfo();

        return (int) $rating_info["numRaters"];
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return new DateTime($this->entry->getPublished()->getText());
    }

    /**
     *
     * @return boolean
     */
    public function is_private()
    {
        return ! ! $this->entry->isVideoPrivate();
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    public function get_tags()
    {
        return implode(",", $this->entry->getVideoTags());
    }
}
