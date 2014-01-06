<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Dailymotion_Element implements Bridge_Api_ElementInterface
{
    /**
     *
     * @var array
     */
    protected $entry;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @param  array                          $entry
     * @param  type                           $type
     * @return Bridge_Api_Dailymotion_Element
     */
    public function __construct(Array $entry, $type)
    {
        $this->entry = $entry;
        $this->type = $type;

        return $this;
    }

    /**
     *
     * @return mixed
     */
    private function get($key, $default = null)
    {
        return isset($this->entry[$key]) ? $this->entry[$key] : $default;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return DateTime::createFromFormat('U', $this->get("created_time"));
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return $this->get("description", '');
    }

    /**
     *
     * @return string
     */
    public function get_id()
    {
        return $this->get("id", '');
    }

    /**
     *
     * @return string
     */
    public function get_thumbnail($width = 120, $height = 90)
    {
        return $this->get("thumbnail_medium_url", '');
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->get("title", '');
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        return $this->type;
    }

    /**
     *
     * @return DateTime
     */
    public function get_updated_on()
    {
        return DateTime::createFromFormat('U', $this->get("modified_time"));
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return $this->get("url", '');
    }

    /**
     *
     * @return boolean
     */
    public function is_private()
    {
        return ! ! $this->get("private", 0);
    }

    /**
     *
     * @return string
     */
    public function get_duration()
    {
        return p4string::format_seconds((int) $this->get('duration', '0'));
    }

    /**
     *
     * @return int
     */
    public function get_view_count()
    {
        return (int) $this->get('views_total', 0);
    }

    /**
     *
     * @return int
     */
    public function get_rating()
    {
        return (int) $this->get('ratings_total', 0);
    }

    /**
     *
     * @return string
     */
    public function get_category()
    {
        return $this->get('channel', '');
    }

    /**
     *
     * @return string
     */
    public function get_tags()
    {
        return implode(",", $this->get('tags', []));
    }
}
