<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Youtube_Container implements Bridge_Api_ContainerInterface
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
     * @var string
     */
    protected $thumbnail;

    public function __construct(Zend_Gdata_App_Entry $entry, $type, $thumbnail)
    {
        $this->entry = $entry;
        $this->type = $type;
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     *
     * @var string
     */
    public function get_id()
    {
        return $this->entry->getPlaylistId()->getText();
    }

    /**
     *
     * @var string
     */
    public function get_thumbnail($width = 120, $height = 90)
    {
        return $this->thumbnail;
    }

    /**
     *
     * @var string
     */
    public function get_url()
    {
        return $this->entry->getAlternateLink()->getHref();
    }

    /**
     *
     * @var string
     */
    public function get_title()
    {
        return $this->entry->getTitle()->getText();
    }

    /**
     *
     * @var string
     */
    public function get_description()
    {
        return $this->entry->getDescription()->getText();
    }

    /**
     *
     * @var DateTime
     */
    public function get_updated_on()
    {
        return new DateTime($this->entry->getUpdated()->getText());
    }

    /**
     *
     * @var DateTime
     */
    public function get_created_on()
    {
        return new DateTime($this->entry->getPublished()->getText());
    }

    /**
     *
     * @var string
     */
    public function get_type()
    {
        return $this->type;
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
