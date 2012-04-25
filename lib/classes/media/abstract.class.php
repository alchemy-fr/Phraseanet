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
 * @package     subdefs
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class media_abstract
{
    /**
     *
     * @var string
     */
    protected $url;

    /**
     *
     * @var int
     */
    protected $height;

    /**
     *
     * @var int
     */
    protected $width;

    const PORTRAIT = 'PORTRAIT';
    const PAYSAGE = 'LANDSCAPE';

    /**
     *
     * @param string $url
     * @param int $width
     * @param int $height
     * @return media
     */
    function __construct($url, $width, $height)
    {
        $this->url = $url;
        $this->height = (int) $height;
        $this->width = (int) $width;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function get_random()
    {
        return md5(time() . mt_rand(100000, 999999));
    }

    /**
     *
     * @return string
     */
    public function get_url()
    {
        return $this->url;
    }

    /**
     *
     * @return int
     */
    public function get_width()
    {
        return $this->width;
    }

    /**
     *
     * @return int
     */
    public function get_height()
    {
        return $this->height;
    }

    /**
     *
     * @return string
     */
    public function get_type()
    {
        return 'image';
    }

    /**
     *
     * @return string
     */
    public function get_orientation()
    {
        if ($this->width > $this->height) {
            return self::PAYSAGE;
        } else {
            return self::PORTRAIT;
        }
    }

    /**
     *
     * @return boolean
     */
    public function is_paysage()
    {
        return $this->get_orientation() == self::PAYSAGE;
    }

    /**
     *
     * @return boolean
     */
    public function is_portrait()
    {
        return $this->get_orientation() == self::PORTRAIT;
    }
}
