<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Formatter;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\FeedItem;

abstract class FeedFormatterAbstract
{
    const PAGE_SIZE = 20;

    /**
     *
     * @param  \DOMDocument $document
     * @param  \DOMNode     $node
     * @param  string       $tagname
     * @param  string       $tagcontent
     * @return \DOMElement
     */
    protected function addTag(\DOMDocument $document, \DOMNode $node, $tagname, $tagcontent = null)
    {
        $tag = $document->createElement($tagname);

        if (trim($tagcontent) !== '') {
            $tag->appendChild($document->createTextNode($tagcontent));
        }
        $node->appendChild($tag);

        return $tag;
    }

    /**
     * @param Application  $app
     * @param \DOMDocument $document
     * @param \DOMNode     $item
     * @param FeedItem     $content
     * @return $this
     */
    protected function addContent(Application $app, \DOMDocument $document, \DOMNode $item, FeedItem $content)
    {
        $preview_sd = $content->getRecord($app)->get_subdef('preview');
        $preview_permalink = $preview_sd->get_permalink();
        $thumbnail_sd = $content->getRecord($app)->get_thumbnail();
        $thumbnail_permalink = $thumbnail_sd->get_permalink();

        $medium = strtolower($content->getRecord($app)->getType());

        if ( ! in_array($medium, ['image', 'audio', 'video'])) {
            return $this;
        }

        if (null === $preview_permalink || null === $thumbnail_permalink) {
            return $this;
        }

        $group = $this->addTag($document, $item, 'media:group');

        $caption = $content->getRecord($app)->get_caption();

        $title_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Title);
        if (null !== $title_field) {
            $str_title = $title_field->get_serialized_values(' ');
            $title = $this->addTag($document, $group, 'media:title', $str_title);
            $title->setAttribute('type', 'plain');
        }

        $desc_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Description);
        if (null !== $desc_field) {
            $str_desc = $desc_field->get_serialized_values(' ');
            $desc = $this->addTag($document, $group, 'media:description', $str_desc);
            $desc->setAttribute('type', 'plain');
        }

        $contrib_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Contributor);
        if (null !== $contrib_field) {
            $str_contrib = $contrib_field->get_serialized_values(' ');
            $contrib = $this->addTag($document, $group, 'media:credit', $str_contrib);
            $contrib->setAttribute('role', 'contributor');
            $contrib->setAttribute('scheme', 'urn:ebu');
        }

        $director_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Creator);
        if (null !== $director_field) {
            $str_director = $director_field->get_serialized_values(' ');
            $director = $this->addTag($document, $group, 'media:credit', $str_director);
            $director->setAttribute('role', 'director');
            $director->setAttribute('scheme', 'urn:ebu');
        }

        $publisher_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Publisher);
        if (null !== $publisher_field) {
            $str_publisher = $publisher_field->get_serialized_values(' ');
            $publisher = $this->addTag($document, $group, 'media:credit', $str_publisher);
            $publisher->setAttribute('role', 'publisher');
            $publisher->setAttribute('scheme', 'urn:ebu');
        }

        $rights_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Rights);
        if (null !== $rights_field) {
            $str_rights = $rights_field->get_serialized_values(' ');
            $rights = $this->addTag($document, $group, 'media:copyright', $str_rights);
        }

        $keyword_field = $caption->get_dc_field(\databox_Field_DCESAbstract::Subject);
        if (null !== $keyword_field) {
            $str_keywords = $keyword_field->get_serialized_values(', ');
            $keywords = $this->addTag($document, $group, 'media:keywords', $str_keywords);
        }

        $duration = $content->getRecord($app)->get_duration();

        if (null !== $preview_permalink) {
            $preview = $this->addTag($document, $group, 'media:content');

            $preview->setAttribute('url', (string) $preview_permalink->get_url());
            $preview->setAttribute('fileSize', $preview_sd->get_size());
            $preview->setAttribute('type', $preview_sd->get_mime());
            $preview->setAttribute('medium', $medium);
            $preview->setAttribute('expression', 'full');
            $preview->setAttribute('isDefault', 'true');

            if (null !== $preview_sd->get_width()) {
                $preview->setAttribute('width', $preview_sd->get_width());
            }
            if (null !== $preview_sd->get_height()) {
                $preview->setAttribute('height', $preview_sd->get_height());
            }
            if (null !== $duration) {
                $preview->setAttribute('duration', $duration);
            }
        }

        if (null !== $thumbnail_permalink) {
            $thumbnail = $this->addTag($document, $group, 'media:thumbnail');

            $thumbnail->setAttribute('url', (string) $thumbnail_permalink->get_url());

            if (null !== $thumbnail_sd->get_width()) {
                $thumbnail->setAttribute('width', $thumbnail_sd->get_width());
            }
            if (null !== $thumbnail_sd->get_height()) {
                $thumbnail->setAttribute('height', $thumbnail_sd->get_height());
            }

            $thumbnail = $this->addTag($document, $group, 'media:content');

            $thumbnail->setAttribute('url', (string) $thumbnail_permalink->get_url());
            $thumbnail->setAttribute('fileSize', $thumbnail_sd->get_size());
            $thumbnail->setAttribute('type', $thumbnail_sd->get_mime());
            $thumbnail->setAttribute('medium', $medium);
            $thumbnail->setAttribute('isDefault', 'false');

            if (null !== $thumbnail_sd->get_width()) {
                $thumbnail->setAttribute('width', $thumbnail_sd->get_width());
            }
            if (null !== $thumbnail_sd->get_height()) {
                $thumbnail->setAttribute('height', $thumbnail_sd->get_height());
            }
            if (null !== $duration) {
                $thumbnail->setAttribute('duration', $duration);
            }
        }

        return $this;
    }

}
