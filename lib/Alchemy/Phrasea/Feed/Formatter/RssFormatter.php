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
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Feed\Link\FeedLink;
use Alchemy\Phrasea\Feed\Link\LinkGeneratorCollection;
use Alchemy\Phrasea\Feed\RSS\FeedRSSImage;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Symfony\Component\HttpFoundation\Response;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Feed\Link\FeedLinkGenerator;

class RssFormatter extends FeedFormatterAbstract implements FeedFormatterInterface
{
    const FORMAT = 'rss';
    const VERSION = '2.0';
    private $linkGenerator;

    /**
     * @param LinkGeneratorCollection $generator
     */
    public function __construct(LinkGeneratorCollection $generator)
    {
        $this->linkGenerator = $generator;
    }

    /**
     * {@inheritdoc}
     */
    public function createResponse(Application $app, FeedInterface $feed, $page, User $user = null, $generator = 'Phraseanet')
    {
        $content = $this->format($feed, $page, $user, $generator, $app);
        $response = new Response($content, 200, ['Content-Type' => 'application/rss+xml']);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function format(FeedInterface $feed, $page, User $user = null, $generator = 'Phraseanet', Application $app = null)
    {
        $next = $prev = null;

        if ($feed->hasPage($page + 1, self::PAGE_SIZE)) {
            if (null === $user) {
                $next = $this->linkGenerator->generatePublic($feed, self::FORMAT, $page + 1);
            } else {
                $next = $this->linkGenerator->generate($feed, $user, self::FORMAT, $page + 1);
            }
        }
        if ($feed->hasPage($page - 1, self::PAGE_SIZE)) {
            if (null === $user) {
                $prev = $this->linkGenerator->generatePublic($feed, self::FORMAT, $page - 1);
            } else {
                $prev = $this->linkGenerator->generate($feed, $user, self::FORMAT, $page - 1);
            }
        }

        if (null !== $user) {
            $link = $this->linkGenerator->generate($feed, $user, self::FORMAT, $page);
        } else {
            $link = $this->linkGenerator->generatePublic($feed, self::FORMAT, $page);
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $doc->standalone = true;

        $root = $this->addTag($doc, $doc, 'rss');

        $root->setAttribute('version', self::VERSION);
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');
        $root->setAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $root->setAttribute('xmlns:dc', 'http://purl.org/dc/elements/1.1/');

        $channel = $this->addTag($doc, $root, 'channel');

        $this->addTag($doc, $channel, 'title', $feed->getTitle());
        $this->addTag($doc, $channel, 'dc:title', $feed->getTitle());
        $this->addTag($doc, $channel, 'description', $feed->getSubtitle());
        if ($link instanceof FeedLink) {
            $this->addTag($doc, $channel, 'link', $link->getURI());
        }

        if (isset($this->language))
            $this->addTag($doc, $channel, 'language', $this->language);
        if (isset($this->copyright))
            $this->addTag($doc, $channel, 'copyright', $this->copyright);
        if (isset($this->managingEditor))
            $this->addTag($doc, $channel, 'managingEditor', $this->managingEditor);
        if (isset($this->webMaster))
            $this->addTag($doc, $channel, 'webMaster', $this->webMaster);
        if (null !== $updated_on = NullableDateTime::format($feed->getUpdatedOn(), DATE_RFC2822)) {
            $this->addTag($doc, $channel, 'pubDate', $updated_on);
        }
        if (isset($this->lastBuildDate) && $this->lastBuildDate instanceof \DateTime) {
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
            $this->addItem($app, $doc, $channel, $item);
        }

        return $doc->saveXML();
    }

    protected function addItem(Application $app, \DOMDocument $document, \DOMNode $node, FeedEntry $entry)
    {
        $item = $this->addTag($document, $node, 'item');
        $feed = $entry->getFeed();

        if ($feed->isPublic()) {
            $link = $app['feed.link-generator-collection']->generatePublic($feed, FeedLinkGenerator::FORMAT_RSS);
        } else {
            $link = $app['feed.link-generator-collection']->generate($feed, $app->getAuthenticatedUser(), FeedLinkGenerator::FORMAT_RSS);
        }

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
            $this->addContent($app, $document, $item, $content);
        }

        return $item;
    }
}
