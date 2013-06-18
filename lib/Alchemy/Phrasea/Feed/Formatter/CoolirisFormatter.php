<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Formatter;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Feed\Link\LinkGeneratorCollection;
use Alchemy\Phrasea\Feed\RSS\FeedRSSImage;
use Symfony\Component\HttpFoundation\Response;

class CoolirisFormatter extends FeedFormatterAbstract implements FeedFormatterInterface
{
    const FORMAT = 'atom';
    const VERSION = '2.0';
    private $linkGenerator;

    public function __construct(LinkGeneratorCollection $generator)
    {
        $this->linkGenerator = $generator;
    }

    public function createResponse(FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet', $app = null)
    {
        $content = $this->format($feed, $page, $user, $generator, $app);
        $response = new Response($content, 200, array('Content-Type' => 'application/rss+xml'));
        $response->setCharset('UTF-8');

        return $response;
    }

    public function format(FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet', $app = null)
    {
        $title = $feed->getTitle();
        $subtitle = $feed->getSubtitle();
        $updated_on = $feed->getUpdatedOn();

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $doc->standalone = true;

        $root = $this->addTag($doc, $doc, 'rss');

        $root->setAttribute('version', self::VERSION);
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $root->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');

        $channel = $this->addTag($doc, $root, 'channel');

        $this->addTag($doc, $channel, 'title', $title);
        $this->addTag($doc, $channel, 'dc:title', $title);
        $this->addTag($doc, $channel, 'description', $subtitle);

        if (null !== $user) {
            $link = $this->linkGenerator->generate($feed, $user, static::FORMAT, $page);
        } else {
            $link = $this->linkGenerator->generatePublic($feed, static::FORMAT, $page);
        }

        if ($link instanceof FeedLink)
            $this->addTag($doc, $channel, 'link', $link->getURI());

        if (isset($this->language))
            $this->addTag($doc, $channel, 'language', $this->language);
        if (isset($this->copyright))
            $this->addTag($doc, $channel, 'copyright', $this->copyright);
        if (isset($this->managingEditor))
            $this->addTag($doc, $channel, 'managingEditor', $this->managingEditor);
        if (isset($this->webMaster))
            $this->addTag($doc, $channel, 'webMaster', $this->webMaster);
        if ($updated_on instanceof DateTime) {
            $updated_on = $updated_on->format(DATE_RFC2822);
            $this->addTag($doc, $channel, 'pubDate', $updated_on);
        }
        if (isset($this->lastBuildDate) && $this->lastBuildDate instanceof DateTime) {
            $last_build = $this->lastBuildDate->format(DATE_RFC2822);
            $this->addTag($doc, $channel, 'lastBuildDate', $last_build);
        }
        if (isset($this->categories) && count($this->categories) > 0) {
            foreach ($this->categories as $category) {
                $this->addTag($doc, $channel, 'category', $category);
            }
        }
        if (isset($this->linkgenerator))
            $this->addTag($doc, $channel, 'generator', $this->linkgenerator);
        if (isset($this->docs))
            $this->addTag($doc, $channel, 'docs', $this->docs);
        if (isset($this->ttl))
            $this->addTag($doc, $channel, 'ttl', $this->ttl);
        if (isset($this->image) && $this->image instanceof FeedRSSImage) {
            $image = $this->addTag($doc, $channel, 'image');
            $this->addTag($doc, $image, 'url', $this->image->getUrl());
            $this->addTag($doc, $image, 'title', $this->image->getTitle());
            $this->addTag($doc, $image, 'link', $this->image->getLink());
            if ($this->image->getWidth())
                $this->addTag($doc, $image, 'width', $this->image->getWidth());
            if ($this->image->getHeight())
                $this->addTag($doc, $image, 'height', $this->image->getHeight());
            if ($this->image->getDescription())
                $this->addTag($doc, $image, 'description', $this->image->getDescription());
        }
        if (isset($this->skipHours) && count($this->skipHours)) {
            $skipHours = $this->addTag($doc, $channel, 'skipHours');
            foreach ($this->skipHours as $hour) {
                $this->addTag($doc, $skipHours, 'hour', $hour);
            }
        }
        if (isset($this->skipDays) && count($this->skipDays) > 0) {
            $skipDays = $this->addTag($doc, $channel, 'skipDays');
            foreach ($this->skipDays as $day) {
                $this->addTag($doc, $skipDays, 'day', $day);
            }
        }
        if ($link instanceof FeedLink) {
            $self_link = $this->addTag($doc, $channel, 'atom:link');
            $self_link->setAttribute('rel', 'self');
            $self_link->setAttribute('href', $link->getURI());
        }

        if ($feed->hasPage($page + 1, static::PAGE_SIZE)) {
            if (null === $user) {
                $next = $this->linkGenerator->generatePublic($feed, static::FORMAT, $page + 1);
            } else {
                $next = $this->linkGenerator->generate($feed, $user, static::FORMAT, $page + 1);
            }
        } else {
            $next = null;
        }
        if ($feed->hasPage($page - 1, static::PAGE_SIZE)) {
            if (null === $user) {
                $prev = $this->linkGenerator->generatePublic($feed, static::FORMAT, $page - 1);
            } else {
                $prev = $this->linkGenerator->generate($feed, $user, static::FORMAT, $page - 1);
            }
        } else {
            $prev = null;
        }

        $prefix = 'atom';

        if ($prev instanceof FeedLink) {
            $prev_link = $this->addTag($doc, $channel, $prefix . 'link');
            $prev_link->setAttribute('rel', 'previous');
            $prev_link->setAttribute('href', $prev->getURI());
        }

        if ($next instanceof FeedLink) {
            $next_link = $this->addTag($doc, $channel, $prefix . 'link');
            $next_link->setAttribute('rel', 'next');
            $next_link->setAttribute('href', $next->getURI());
        }

        foreach ($feed->getEntries() as $item) {
            $this->addItem($app, $doc, $channel, $item);
        }

        return $doc->saveXML();
    }

    protected function addItem(Application $app, \DOMDocument $document, \DOMNode $feed, FeedEntry $entry)
    {
        foreach ($entry->get_content() as $content) {
            $this->addContent($app, $document, $feed, $entry, $content);
        }
    }

    protected function addContent(Application $app, \DOMDocument $document, \DOMNode $node, FeedItem $content)
    {

        $preview_sd = $content->getRecord($app)->get_subdef('preview');
        $preview_permalink = $preview_sd->get_permalink();
        $thumbnail_sd = $content->getRecord($app)->get_thumbnail();
        $thumbnail_permalink = $thumbnail_sd->get_permalink();

        $medium = strtolower($content->getRecord($app)->get_type());

        if ( ! in_array($medium, array('image', 'audio', 'video'))) {
            return $this;
        }

        if (! $preview_permalink || ! $thumbnail_permalink) {
            return $this;
        }

        //add item node to channel node
        $item = $this->addTag($document, $node, 'item');

        $caption =  $content->getRecord($app)->get_caption();

        $title_field = $caption->get_dc_field(databox_Field_DCESAbstract::Title);
        if ($title_field) {
            $str_title = $title_field->get_serialized_values(' ');
        } else {
            $str_title = $content->getRecord($app)->get_title();
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

        $duration = $content->getRecord($app)->get_duration();

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
