<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
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
class Feed_XML_Cooliris extends Feed_XML_Abstract implements Feed_XML_Interface
{
    /**
     * RSS version
     */
    const VERSION = '2.0';

    /**
     *
     * @var Array
     */
    protected $required_fields = array('title', 'subtitle', 'link');

    /**
     *
     * @var string
     */
    protected $language;

    /**
     *
     * @var string
     */
    protected $copyright;

    /**
     *
     * @var string
     */
    protected $managingEditor;

    /**
     *
     * @var string
     */
    protected $webMaster;

    /**
     *
     * @var DateTime
     */
    protected $lastBuildDate;

    /**
     *
     * @var string
     */
    protected $categories;

    /**
     *
     * @var string
     */
    protected $docs = 'http://blogs.law.harvard.edu/tech/rss';

    /**
     *
     * @var int
     */
    protected $ttl;

    /**
     *
     * @var Feed_XML_RSS_Image
     */
    protected $image;

    /**
     *
     * @var string
     */
    protected $skipHours = array();

    /**
     *
     * @var string
     */
    protected $skipDays = array();

    /**
     *
     * @var string
     */
    protected $mimetype = 'application/rss+xml';

    /**
     *
     * @param  string       $language
     * @return Feed_XML_RSS
     */
    public function set_language($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     *
     * @param  string       $language
     * @return Feed_XML_RSS
     */
    public function set_copyright($copyright)
    {
        $this->copyright = $copyright;

        return $this;
    }

    /**
     *
     * @param  string       $managingEditor
     * @return Feed_XML_RSS
     */
    public function set_managingEditor($managingEditor)
    {
        $this->managingEditor = $managingEditor;

        return $this;
    }

    /**
     *
     * @param  string       $webMaster
     * @return Feed_XML_RSS
     */
    public function set_webMaster($webMaster)
    {
        $this->webMaster = $webMaster;

        return $this;
    }

    /**
     *
     * @param  DateTime     $lastBuildDate
     * @return Feed_XML_RSS
     */
    public function set_lastBuildDate(DateTime $lastBuildDate)
    {
        $this->lastBuildDate = $lastBuildDate;

        return $this;
    }

    /**
     *
     * @param  string       $category
     * @return Feed_XML_RSS
     */
    public function set_category($category)
    {
        $this->categories[] = $category;

        return $this;
    }

    /**
     *
     * @param  string       $docs
     * @return Feed_XML_RSS
     */
    public function set_docs($docs)
    {
        $this->docs = $docs;

        return $this;
    }

    /**
     *
     * @param  int          $ttl
     * @return Feed_XML_RSS
     */
    public function set_ttl($ttl)
    {
        $this->ttl = $ttl;

        return $this;
    }

    /**
     *
     * @param  Feed_XML_RSS_Image $image
     * @return Feed_XML_RSS
     */
    public function set_image(Feed_XML_RSS_Image $image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     *
     * @param  string       $skipHours
     * @return Feed_XML_RSS
     */
    public function set_skipHour($hour)
    {
        $this->skipHours[] = (int) $hour;

        return $this;
    }

    /**
     *
     * @param  string       $skipDays
     * @return Feed_XML_RSS
     */
    public function set_skipDays($day)
    {
        $this->skipDays[] = $day;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function render()
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $doc->standalone = true;

        $root = $this->addTag($doc, $doc, 'rss');

        $root->setAttribute('version', self::VERSION);
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $root->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');

        $channel = $this->addTag($doc, $root, 'channel');

        $this->addTag($doc, $channel, 'title', $this->title);
        $this->addTag($doc, $channel, 'dc:title', $this->title);
        $this->addTag($doc, $channel, 'description', $this->subtitle);
        if ($this->link instanceof Feed_Link)
            $this->addTag($doc, $channel, 'link', $this->link->get_href());

        if ($this->language)
            $this->addTag($doc, $channel, 'language', $this->language);
        if ($this->copyright)
            $this->addTag($doc, $channel, 'copyright', $this->copyright);
        if ($this->managingEditor)
            $this->addTag($doc, $channel, 'managingEditor', $this->managingEditor);
        if ($this->webMaster)
            $this->addTag($doc, $channel, 'webMaster', $this->webMaster);
        if ($this->updated_on instanceof DateTime) {
            $updated_on = $this->updated_on->format(DATE_RFC2822);
            $this->addTag($doc, $channel, 'pubDate', $updated_on);
        }
        if ($this->lastBuildDate instanceof DateTime) {
            $last_build = $this->lastBuildDate->format(DATE_RFC2822);
            $this->addTag($doc, $channel, 'lastBuildDate', $last_build);
        }
        if (count($this->categories) > 0) {
            foreach ($this->categories as $category) {
                $this->addTag($doc, $channel, 'category', $category);
            }
        }
        if ($this->generator)
            $this->addTag($doc, $channel, 'generator', $this->generator);
        if ($this->docs)
            $this->addTag($doc, $channel, 'docs', $this->docs);
        if ($this->ttl)
            $this->addTag($doc, $channel, 'ttl', $this->ttl);
        if ($this->image instanceof Feed_XML_RSS_Image) {
            $image = $this->addTag($doc, $channel, 'image');
            $this->addTag($doc, $image, 'url', $this->image->get_url());
            $this->addTag($doc, $image, 'title', $this->image->get_title());
            $this->addTag($doc, $image, 'link', $this->image->get_link());
            if ($this->image->get_width())
                $this->addTag($doc, $image, 'width', $this->image->get_width());
            if ($this->image->get_height())
                $this->addTag($doc, $image, 'height', $this->image->get_height());
            if ($this->image->get_description())
                $this->addTag($doc, $image, 'description', $this->image->get_description());
        }
        if (count($this->skipHours)) {
            $skipHours = $this->addTag($doc, $channel, 'skipHours');
            foreach ($this->skipHours as $hour) {
                $this->addTag($doc, $skipHours, 'hour', $hour);
            }
        }
        if (count($this->skipDays) > 0) {
            $skipDays = $this->addTag($doc, $channel, 'skipDays');
            foreach ($this->skipDays as $day) {
                $this->addTag($doc, $skipDays, 'day', $day);
            }
        }
        if ($this->link instanceof Feed_Link) {
            $self_link = $this->addTag($doc, $channel, 'atom:link');
            $self_link->setAttribute('rel', 'self');
            $self_link->setAttribute('href', $this->link->get_href());
        }

        $this->add_navigation($doc, $channel, true);

        foreach ($this->items as $item) {
            $this->add_item($doc, $channel, $item);
        }

        return $doc->saveXML();
    }

    /**
     *
     * @param  DOMDocument        $document
     * @param  DOMNode            $node
     * @param  Feed_Entry_Adapter $entry
     * @return DOMElement
     */
    protected function add_item(DOMDocument $document, DOMNode $node, Feed_Entry_Adapter $entry)
    {
        foreach ($entry->get_content() as $content) {
            $this->addContent($document, $node, $entry, $content);
        }

        return;
    }

    /**
     *
     * @param  DOMDocument        $document
     * @param  DOMNode            $item
     * @param  Feed_Entry_Item    $content
     * @return Feed_XML_Interface
     */
    protected function addContent(DOMDocument $document, DOMNode $node, Feed_Entry_Adapter $entry, Feed_Entry_Item $content)
    {

        $preview_sd = $content->get_record()->get_subdef('preview');
        $preview_permalink = $preview_sd->get_permalink();
        $thumbnail_sd = $content->get_record()->get_thumbnail();
        $thumbnail_permalink = $thumbnail_sd->get_permalink();

        $medium = strtolower($content->get_record()->get_type());

        if ( ! in_array($medium, array('image', 'audio', 'video'))) {
            return $this;
        }

        if ( ! $preview_permalink || ! $thumbnail_permalink) {
            return $this;
        }

        //add item node to channel node
        $item = $this->addTag($document, $node, 'item');

        $caption =  $content->get_record()->get_caption();

        $title_field = $caption->get_dc_field(databox_Field_DCESAbstract::Title);
        if ($title_field) {
            $str_title = $title_field->get_serialized_values(' ');
        } else {
            $str_title = $content->get_record()->get_title();
        }

        //attach tile node to item node
        $title = $this->addTag($document, $item, 'title', $str_title);

        $desc_field = $caption->get_dc_field(databox_Field_DCESAbstract::Description);
        if ($desc_field) {
            $str_desc = $desc_field->get_serialized_values(' ');
        } else {
            $str_desc = '';
        }

        //attach desc node to item node
        $desc = $this->addTag($document, $item, 'description', $str_desc);

        $duration = $content->get_record()->get_duration();

        if ($preview_permalink) {
            $preview = $this->addTag($document, $item, 'media:content');

            $preview->setAttribute('url', $preview_permalink->get_url());
            $preview->setAttribute('fileSize', $preview_sd->get_size());
            $preview->setAttribute('type', $preview_sd->get_mime());
            $preview->setAttribute('medium', $medium);
            $preview->setAttribute('expression', 'full');
            $preview->setAttribute('isDefault', 'true');

            if ($preview_sd->get_width())
                $preview->setAttribute('width', $preview_sd->get_width());
            if ($preview_sd->get_height())
                $preview->setAttribute('height', $preview_sd->get_height());
            if ($duration)
                $preview->setAttribute('duration', $duration);
        }

        if ($thumbnail_permalink) {
            $thumbnail = $this->addTag($document, $item, 'media:thumbnail');

            $thumbnail->setAttribute('url', $thumbnail_permalink->get_url());

            if ($thumbnail_sd->get_width())
                $thumbnail->setAttribute('width', $thumbnail_sd->get_width());
            if ($thumbnail_sd->get_height())
                $thumbnail->setAttribute('height', $thumbnail_sd->get_height());

            $thumbnail = $this->addTag($document, $item, 'media:content');

            $thumbnail->setAttribute('url', $thumbnail_permalink->get_url());
            $thumbnail->setAttribute('fileSize', $thumbnail_sd->get_size());
            $thumbnail->setAttribute('type', $thumbnail_sd->get_mime());
            $thumbnail->setAttribute('medium', $medium);
            $thumbnail->setAttribute('isDefault', 'false');

            if ($thumbnail_sd->get_width())
                $thumbnail->setAttribute('width', $thumbnail_sd->get_width());
            if ($thumbnail_sd->get_height())
                $thumbnail->setAttribute('height', $thumbnail_sd->get_height());
            if ($duration)
                $thumbnail->setAttribute('duration', $duration);
        }

        return $this;
    }
}
