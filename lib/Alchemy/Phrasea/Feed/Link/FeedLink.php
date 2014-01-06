<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Feed\Link;

class FeedLink implements FeedLinkInterface
{
    /** @var string */
    protected $mimetype;

    /** @var string */
    protected $title;

    /** @var string */
    protected $uri;

    /**
     * @param string $uri
     * @param string $title
     * @param string $mimetype
     */
    public function __construct($uri, $title, $mimetype)
    {
        $this->mimetype = $mimetype;
        $this->uri = $uri;
        $this->title = $title;
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype()
    {
        return $this->mimetype;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getURI()
    {
        return $this->uri;
    }
}
