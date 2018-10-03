<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class Bridge_Api_Flickr_Element implements Bridge_Api_ElementInterface
{
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
    protected $user_id;

    /**
     *
     * @param SimpleXMLElement $entry
     * @param string           $user_id
     * @param string           $type
     * @param Boolean          $entry_from_list
     *
     * @return Bridge_Api_Flickr_Element
     */
    public function __construct(SimpleXMLElement $entry, $user_id, $type, $entry_from_list = true)
    {
        $this->entry = [];
        $this->type = $type;

        if ($entry_from_list)
            $this->init_from_list_entry($entry);
        else
            $this->init_from_single_entry($entry);

        $this->user_id = (string) $user_id;

        return $this;
    }

    private function init_from_list_entry($entry)
    {
        $this->entry["id"] = isset($entry["id"]) ? (string) $entry["id"] : "";
        $this->entry["url"] = $this->generate_page_url($entry);
        $this->entry["thumbnail"] = $this->generate_thumb_url($entry, 's');
        $this->entry["title"] = isset($entry["title"]) ? (string) $entry["title"] : "";
        $this->entry["updated_on"] = isset($entry["lastupdate"]) ? (string) $entry["lastupdate"] : null;
        $this->entry["created_on"] = isset($entry["dateupload"]) ? (string) $entry["dateupload"] : null;
        $this->entry["views"] = isset($entry["views"]) ? (string) $entry["views"] : 0;
        $this->entry["ispublic"] = isset($entry["ispublic"]) ?  ! ! (string) $entry["ispublic"] : false;
        $this->entry["description"] = isset($entry->description) ? (string) $entry->description : "";
        $this->entry["tags"] = isset($entry["tags"]) ? (string) $entry["tags"] : "";
    }

    private function init_from_single_entry($entry)
    {
        $photo = $entry->photo;
        $url = '';

        foreach ($photo->urls->url as $one_url) {
            if ($one_url["type"] == "photopage")
                $url = (string) $one_url;
        }
        $dates = $photo->dates;
        $visibility = $photo->visibility;
        $tags = [];
        foreach ($photo->tags->tag as $one_tag) {
            $tags[] = $one_tag;
        }

        $this->entry["id"] = isset($photo["id"]) ? (string) $photo["id"] : '';
        $this->entry["url"] = $url;
        $this->entry["thumbnail"] = $this->generate_thumb_url($photo, 's');
        $this->entry["title"] = isset($photo->title) ? (string) $photo->title : '';
        $this->entry["updated_on"] = isset($dates["lastupdate"]) ? (string) $dates["lastupdate"] : null;
        $this->entry["created_on"] = isset($dates["posted"]) ? (string) $dates["posted"] : null;
        $this->entry["views"] = isset($photo["views"]) ? (string) $photo["views"] : 0;
        $this->entry["ispublic"] = isset($visibility["ispublic"]) ?  ! ! (string) $visibility["ispublic"] : false;
        $this->entry["description"] = isset($photo->description) ? (string) $photo->description : "";
        $this->entry["tags"] = implode(" ", $tags);
    }

    private function generate_page_url($entry)
    {
        if (isset($entry["owner"]) && isset($entry["id"])) {
            return sprintf("http://www.flickr.com/%ss/%s/%s/", $this->type, (string) $entry["owner"], (string) $entry["id"]);
        }

        return "";
    }

    /**
     *
     * @param  type   $entry
     * @param  type   $size
     * @param  type   $extension
     * @return string
     */
    private function generate_thumb_url($entry, $size = '', $extension = '')
    {
        if (isset($entry["url_t"])) {
            return (string) $entry["url_t"];
        }

        if ( ! isset($entry["farm"]) || ! isset($entry["farm"]) || ! isset($entry["farm"]) || ! isset($entry["farm"]) || ! isset($entry["farm"])) {
            return '';
        }

        $farm = (string) $entry["farm"];
        $server_id = (string) $entry["server"];
        $id_photo = (string) $entry["id"];
        $secret = (string) $entry["secret"];

        if (empty($size) && empty($extension)) {
            return sprintf('https://farm%s.static.flickr.com/%s/%s_%s.jpg', $farm, $server_id, $id_photo, $secret);
        } elseif ( ! empty($size) && ! empty($extension)) {
            return sprintf('https://farm%s.static.flickr.com/%s/%s_%s_%s.jpg', $farm, $server_id, $id_photo, $secret, $size);
        } elseif ( ! empty($size)) {
            return sprintf('https://farm%s.static.flickr.com/%s/%s_%s_%s.jpg', $farm, $server_id, $id_photo, $secret, $size, '.jpg');
        } elseif ( ! empty($extension)) {
            return sprintf('https://farm%s.static.flickr.com/%s/%s_%s_o.%s', $farm, $server_id, $id_photo, $secret, $extension);
        } else {
            return "";
        }
    }

    /**
     *
     * @return string
     */
    public function get_id()
    {
        return $this->entry["id"];
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return $this->entry["url"];
    }

    /**
     *
     * @return string
     */
    public function get_thumbnail()
    {
        return $this->entry["thumbnail"];
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->entry["title"];
    }

    /**
     *
     * @return null
     */
    public function get_description()
    {
        return $this->entry["description"];
    }

    /**
     *
     * @return null
     */
    public function get_updated_on()
    {
        $date = $this->entry["updated_on"];
        if ($date)
            $date = DateTime::createFromFormat('U', $date);

        return $date;
    }

    /**
     *
     * @return null
     */
    public function get_category()
    {
        return '';
    }

    /**
     *
     * @return null
     */
    public function get_duration()
    {
        return '';
    }

    /**
     *
     * @return int
     */
    public function get_view_count()
    {
        return (int) $this->entry["views"];
    }

    /**
     *
     * @return null
     */
    public function get_rating()
    {
        return null;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        $date = $this->entry["created_on"];
        if ($date)
            $date = DateTime::createFromFormat('U', $date);

        return $date;
    }

    /**
     *
     * @return null
     */
    public function is_private()
    {
        return ! $this->entry["ispublic"];
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
     * @return string
     */
    public function get_tags()
    {
        return $this->entry["tags"];
    }
}
