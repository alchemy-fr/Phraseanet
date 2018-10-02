<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Flickr_Container implements Bridge_Api_ContainerInterface
{
    /**
     *
     * @var SimpleXMLElement
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
     * @var string
     */
    protected $user_id;

    /**
     *
     * @param  SimpleXMLElement            $entry
     * @param  string                      $user_id
     * @param  string                      $type
     * @param  string                      $thumbnail
     * @return Bridge_Api_Flickr_Container
     */
    public function __construct(SimpleXMLElement $entry, $user_id, $type, $thumbnail)
    {
        $this->entry = $entry;
        $this->type = $type;
        $this->thumbnail = $thumbnail;

        $this->user_id = (string) $user_id;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_id()
    {
        return (string) $this->entry['id'];
    }

    /**
     *
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
    public function get_url()
    {
        return sprintf(
                'https://secure.flickr.com/photos/%s/sets/%s'
                , $this->user_id
                , $this->entry['id']
        );
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return (string) $this->entry->title;
    }

    /**
     *
     * @return string
     */
    public function get_description()
    {
        return (string) $this->entry->description;
    }

    /**
     *
     * @return DateTime
     */
    public function get_updated_on()
    {
        return DateTime::createFromFormat('U', (string) $this->entry['date_update']);
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return DateTime::createFromFormat('U', (string) $this->entry['date_create']);
    }

    /**
     *
     * @return string
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
