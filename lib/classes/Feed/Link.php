<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
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
class Feed_Link implements Feed_LinkInterface
{
    /**
     *
     * @var string
     */
    protected $mimetype;

    /**
     *
     * @var string
     */
    protected $title;

    /**
     *
     * @var string
     */
    protected $href;

    /**
     *
     * @param  string    $href
     * @param  string    $title
     * @param  string    $mimetype
     * @return Feed_Link
     */
    public function __construct($href, $title, $mimetype)
    {
        $this->mimetype = $mimetype;
        $this->href = $href;
        $this->title = $title;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_mimetype()
    {
        return $this->mimetype;
    }

    /**
     *
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     *
     * @return string
     */
    public function get_href()
    {
        return $this->href;
    }
}
