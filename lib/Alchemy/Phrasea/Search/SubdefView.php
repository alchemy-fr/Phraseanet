<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Assert\Assertion;

class SubdefView
{
    /**
     * @var \media_subdef
     */
    private $subdef;

    /**
     * @var PermalinkView
     */
    private $permalinkView;

    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $urlTTL;

    public function __construct(\media_subdef $subdef)
    {
        $this->subdef = $subdef;
    }

    /**
     * @return \media_subdef
     */
    public function getSubdef()
    {
        return $this->subdef;
    }

    /**
     * @param PermalinkView $permalinkView
     */
    public function setPermalinkView($permalinkView)
    {
        $this->permalinkView = $permalinkView;
    }

    /**
     * @return PermalinkView
     */
    public function getPermalinkView()
    {
        return $this->permalinkView;
    }

    /**
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = (string)$url;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param null|int $urlTTL
     */
    public function setUrlTTL($urlTTL)
    {
        Assertion::nullOrIntegerish($urlTTL);

        $this->urlTTL = null === $urlTTL ? null : (int)$urlTTL;
    }

    /**
     * @return null|int
     */
    public function getUrlTTL()
    {
        return $this->urlTTL;
    }
}
