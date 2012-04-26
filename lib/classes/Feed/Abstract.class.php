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
abstract class Feed_Abstract
{
    /**
     *
     */
    const FORMAT_RSS = 'rss';
    /**
     *
     */
    const FORMAT_ATOM = 'atom';
    /**
     *
     */
    const FORMAT_COOLIRIS = 'cooliris';

    /**
     *
     * @var appbox
     */
    protected $appbox;

    /**
     *
     * @var string
     */
    protected $title;

    /**
     *
     * @var string
     */
    protected $subtitle;

    /**
     *
     * @var DateTime
     */
    protected $created_on;

    /**
     *
     * @var DateTime
     */
    protected $updated_on;

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
    public function get_subtitle()
    {
        return $this->subtitle;
    }

    /**
     *
     * @return DateTime
     */
    public function get_created_on()
    {
        return $this->created_on;
    }

    /**
     *
     * @return DateTime
     */
    public function get_updated_on()
    {
        return $this->updated_on;
    }
}
