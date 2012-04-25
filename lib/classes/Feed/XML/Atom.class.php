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
class Feed_XML_Atom extends Feed_XML_Abstract implements Feed_XML_Interface
{
    /**
     *
     * @var string
     */
    protected $id;

    /**
     *
     * @var string
     */
    protected $author_name;

    /**
     *
     * @var string
     */
    protected $author_email;

    /**
     *
     * @var string
     */
    protected $author_url;

    /**
     *
     * @var boolean
     */
    protected $author = false;

    /**
     *
     * @var string
     */
    protected $icon;

    /**
     *
     * @var string
     */
    protected $mimetype = 'application/atom+xml';

    /**
     *
     * @return string
     */
    public function render()
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $document->standalone = true;

        $root = $this->addTag($document, $document, 'feed');
        $root->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');

        $this->addTag($document, $root, 'title', $this->title);
        if ($this->updated_on instanceof DateTime) {
            $updated_on = $this->updated_on->format(DATE_ATOM);
            $this->addTag($document, $root, 'updated', $updated_on);
        }

        if ($this->link instanceof Feed_Link) {
            $link = $this->addTag($document, $root, 'link');
            $link->setAttribute('rel', 'self');
            $link->setAttribute('href', $this->link->get_href());
            $this->addTag($document, $root, 'id', $this->link->get_href());
        }

        $this->add_navigation($document, $root, false);

        if ($this->generator)
            $this->addTag($document, $root, 'generator', $this->generator);
        if ($this->subtitle)
            $this->addTag($document, $root, 'subtitle', $this->subtitle);
        if ($this->icon)
            $this->addTag($document, $root, 'icon', $this->icon);
        if ($this->author) {
            $author = $this->addTag($document, $root, 'author');
            if ($this->author_email)
                $this->addTag($document, $author, 'email', $this->author_email);
            if ($this->author_name)
                $this->addTag($document, $author, 'name', $this->author_name);
            if ($this->author_url)
                $this->addTag($document, $author, 'uri', $this->author_url);
        }

        foreach ($this->items as $item) {
            $this->add_item($document, $root, $item);
        }

        return $document->saveXML();
    }

    /**
     *
     * @param DOMDocument $document
     * @param DOMElement $feed
     * @param Feed_Entry_Adapter $entry
     * @return DOMElement
     */
    protected function add_item(DOMDocument $document, DOMElement $feed, Feed_Entry_Adapter $entry)
    {
        $entry_node = $this->addTag($document, $feed, 'entry');

        $link = sprintf('%sentry/%d/', $this->link->get_href(), $entry->get_id());

        $this->addTag($document, $entry_node, 'id', $link);
        $link_tag = $this->addTag($document, $entry_node, 'link');
        $link_tag->setAttribute('rel', 'self');
        $link_tag->setAttribute('href', $link);

        $updated_on = $entry->get_updated_on()->format(DATE_ATOM);
        $created_on = $entry->get_created_on()->format(DATE_ATOM);

        $this->addTag($document, $entry_node, 'updated', $updated_on);
        $this->addTag($document, $entry_node, 'published', $created_on);
        $this->addTag($document, $entry_node, 'title', $entry->get_title());
        $author = $this->addTag($document, $entry_node, 'author');

        if ($entry->get_author_email())
            $this->addTag($document, $author, 'email', $entry->get_author_email());
        if ($entry->get_author_name())
            $this->addTag($document, $author, 'name', $entry->get_author_name());

        $this->addTag($document, $entry_node, 'content', $entry->get_subtitle());


        foreach ($entry->get_content() as $content) {
            $this->addContent($document, $entry_node, $content);
        }

        return $entry_node;
    }

    /**
     *
     * @param string $author_name
     * @return Feed_XML_Atom
     */
    public function set_author_name($author_name)
    {
        $this->author = true;
        $this->author_name = $author_name;

        return $this;
    }

    /**
     *
     * @param string $author_name
     * @return Feed_XML_Atom
     */
    public function set_author_email($author_email)
    {
        $this->author = true;
        $this->author_email = $author_email;

        return $this;
    }

    /**
     *
     * @param string $author_name
     * @return Feed_XML_Atom
     */
    public function set_author_url($author_url)
    {
        $this->author = true;
        $this->author_url = $author_url;

        return $this;
    }
}
