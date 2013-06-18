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
use Alchemy\Phrasea\Feed\Link\FeedLink;
use Alchemy\Phrasea\Feed\Link\LinkGeneratorCollection;
use Alchemy\Phrasea\Feed\RSS\FeedRSSImage;
use Symfony\Component\HttpFoundation\Response;

class RssFormatter extends FeedFormatterAbstract implements FeedFormatterInterface
{
    const FORMAT = 'rss';
    const VERSION = '2.0';
    private $linkGenerator;

    public function __construct(LinkGeneratorCollection $generator)
    {
        $this->linkGenerator = $generator;
    }

    public function createResponse(FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet')
    {
        $content = $this->format($feed, $page, $user, $generator);
        $response = new Response($content, 200, array('Content-Type' => 'application/rss+xml'));
        $response->setCharset('UTF-8');

        return $response;
    }

    public function format(FeedInterface $feed, $page, \User_Adapter $user = null, $generator = 'Phraseanet', $app = null)
    {
        $title = $feed->getTitle();
        $subtitle = $feed->getSubtitle();
        $updated_on = $feed->getUpdatedOn();

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

        if (null !== $user) {
            $link = $this->linkGenerator->generate($feed, $user, static::FORMAT, $page);
        } else {
            $link = $this->linkGenerator->generatePublic($feed, static::FORMAT, $page);
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $doc->standalone = true;

        $root = $this->addTag($doc, $doc, 'rss');

        $root->setAttribute('version', static::VERSION);
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $root->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');

        $channel = $this->addTag($doc, $root, 'channel');

        $this->addTag($doc, $channel, 'title', $title);
        $this->addTag($doc, $channel, 'dc:title', $title);
        $this->addTag($doc, $channel, 'description', $subtitle);
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
        if ($updated_on instanceof \DateTime) {
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
        if (isset($generator))
            $this->addTag($doc, $channel, 'generator', $generator);
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

        $prefix = 'atom:';

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
            $this->addItem($doc, $channel, $item);
        }

        return $doc->saveXML();
    }

    protected function addItem(\DOMDocument $document, \DOMNode $node, FeedEntry $entry)
    {
        $item = $this->addTag($document, $node, 'item');

        $link = $entry->getLink();

        $this->addTag($document, $item, 'title', $entry->getTitle());
        $this->addTag($document, $item, 'description', $entry->getSubtitle());

        $author = sprintf(
            '%s (%s)'
            , $entry->getAuthorEmail()
            , $entry->getAuthorName()
        );
        $created_on = $entry->getCreatedOn()->format(DATE_RFC2822);

        $this->addTag($document, $item, 'author', $author);
        $this->addTag($document, $item, 'pubDate', $created_on);
        $this->addTag($document, $item, 'guid', $link->getURI());
        $this->addTag($document, $item, 'link', $link->getURI());

        /**
         *  Missing :
         *
         *  category  Includes the item in one or more categories. More.
         *  comments  URL of a page for comments relating to the item. More.
         *  enclosure  Describes a media object that is attached to the item. More.
         *  source    The RSS channel that the item came from. More.
         *
         */
        foreach ($entry->getItems() as $content) {
            $this->addContent($document, $item, $entry, $content);
        }

        return $item;
    }
}
