<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Dailymotion_Container implements Bridge_Api_ContainerInterface
{
    /**
     *
     * @var Array
     */
    protected $entry;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @var string
     */
    protected $thumbnail;

    /**
     *
     * @param  array                            $entry
     * @param  String                           $type
     * @param  String                           $thumbnail
     * @param  String                           $url
     * @return Bridge_Api_Dailymotion_Container
     */
    public function __construct(Array $entry, $type, $thumbnail = '', $url = '')
    {
        $this->entry = $entry;
        $this->type = $type;
        $this->thumbnail = $thumbnail;
        $this->url = $url;

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
     * @return void
     */
    public function get_created_on()
    {
        return;
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
     * @param  type   $width
     * @param  type   $height
     * @return string
     */
    public function get_thumbnail($width = 120, $height = 90)
    {
        return $this->thumbnail;
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->get("name", '');
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
     * @return void
     */
    public function get_updated_on()
    {
        return;
    }

    /**
     *
     * @return void
     */
    public function get_url()
    {
        return $this->url;
    }

    public function get_duration()
    {
        return '';
    }

    public function get_category()
    {
        return '';
    }

    public function is_private()
    {
        return null;
    }
}
