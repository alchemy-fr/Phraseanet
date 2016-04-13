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
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Feed\FeedInterface;
use Alchemy\Phrasea\Feed\Link\FeedLink;
use Alchemy\Phrasea\Feed\Link\LinkGeneratorCollection;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Utilities\NullableDateTime;
use Symfony\Component\HttpFoundation\Response;

class AtomFormatter extends FeedFormatterAbstract implements FeedFormatterInterface
{
    const FORMAT = 'atom';
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
        $response = new Response($content, 200, ['Content-Type' => 'application/atom+xml']);

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function format(FeedInterface $feed, $page, User $user = null, $generator = 'Phraseanet', Application $app = null)
    {
        $document = new \DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;
        $document->standalone = true;

        $root = $this->addTag($document, $document, 'feed');
        $root->setAttribute('xmlns', 'http://www.w3.org/2005/Atom');
        $root->setAttribute('xmlns:media', 'http://search.yahoo.com/mrss/');

        $this->addTag($document, $root, 'title', $feed->getTitle());
        if (null !== $updated_on = NullableDateTime::format($feed->getUpdatedOn())) {
            $this->addTag($document, $root, 'updated', $updated_on);
        }

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
            $feedlink = $this->linkGenerator->generate($feed, $user, self::FORMAT, $page);
        } else {
            $feedlink = $this->linkGenerator->generatePublic($feed, self::FORMAT, $page);
        }

        if ($feedlink instanceof FeedLink) {
            $link = $this->addTag($document, $root, 'link');
            $link->setAttribute('rel', 'self');
            $link->setAttribute('href', $feedlink->getURI());
            $this->addTag($document, $root, 'id', $feedlink->getURI());
        }

        if ($prev instanceof FeedLink) {
            $prev_link = $this->addTag($document, $root, 'link');
            $prev_link->setAttribute('rel', 'previous');
            $prev_link->setAttribute('href', $prev->getURI());
        }

        if ($next instanceof FeedLink) {
            $next_link = $this->addTag($document, $root, 'link');
            $next_link->setAttribute('rel', 'next');
            $next_link->setAttribute('href', $next->getURI());
        }

        if (null !== $generator) {
            $this->addTag($document, $root, 'generator', $generator);
        }
        if (null !== $feed->getSubtitle()) {
            $this->addTag($document, $root, 'subtitle', $feed->getSubtitle());
        }
        if (null !== $feed->getIconUrl()) {
            $this->addTag($document, $root, 'icon', $feed->getIconUrl());
        }
        if (isset($this->author)) {
            $author = $this->addTag($document, $root, 'author');
            if (isset($this->author_email))
                $this->addTag($document, $author, 'email', $this->author_email);
            if (isset($this->author_name))
                $this->addTag($document, $author, 'name', $this->author_name);
            if (isset($this->author_url))
                $this->addTag($document, $author, 'uri', $this->author_url);
        }

        foreach ($feed->getEntries() as $item) {
            $this->addItem($app, $document, $root, $item, $feedlink);
        }

        return $document->saveXML();
    }

    protected function addItem(Application $app, \DOMDocument $document, \DOMNode $feed, FeedEntry $entry, FeedLink $link)
    {
        $entry_node = $this->addTag($document, $feed, 'entry');

        $link = sprintf('%sentry/%d/', $link->getURI(), $entry->getId());

        $this->addTag($document, $entry_node, 'id', $link);
        $link_tag = $this->addTag($document, $entry_node, 'link');
        $link_tag->setAttribute('rel', 'self');
        $link_tag->setAttribute('href', $link);

        $updated_on = $entry->getUpdatedOn()->format(DATE_ATOM);
        $created_on = $entry->getCreatedOn()->format(DATE_ATOM);

        $this->addTag($document, $entry_node, 'updated', $updated_on);
        $this->addTag($document, $entry_node, 'published', $created_on);
        $this->addTag($document, $entry_node, 'title', $entry->getTitle());
        $author = $this->addTag($document, $entry_node, 'author');

        if ($entry->getAuthorEmail()) {
            $this->addTag($document, $author, 'email', $entry->getAuthorEmail());
        }
        if ($entry->getAuthorName()) {
            $this->addTag($document, $author, 'name', $entry->getAuthorName());
        }

        $this->addTag($document, $entry_node, 'content', $entry->getSubtitle());

        foreach ($entry->getItems() as $content) {
            $this->addContent($app, $document, $entry_node, $content);
        }

        return $entry_node;
    }
}
