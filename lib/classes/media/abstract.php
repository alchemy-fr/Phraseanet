<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Guzzle\Http\Url;

abstract class media_abstract
{
    const PORTRAIT = 'PORTRAIT';
    const LANDSCAPE = 'LANDSCAPE';

    /**
     * @var Url
     */
    protected $url;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var int
     */
    protected $width;

    /**
     * @param int $width
     * @param int $height
     * @param Url|null $url
     */
    public function __construct($width, $height, Url $url = null)
    {
        $this->url = $url;
        $this->height = (int) $height;
        $this->width = (int) $width;
    }

    /**
     * @return Url
     */
    public function get_url()
    {
        return $this->url;
    }

    /**
     * @return int
     */
    public function get_width()
    {
        return $this->width;
    }

    /**
     * @return int
     */
    public function get_height()
    {
        return $this->height;
    }

    /**
     * @return string
     */
    public function get_type()
    {
        return 'image';
    }

    /**
     * @return string
     */
    public function getOrientation()
    {
        if ($this->width > $this->height) {
            return self::LANDSCAPE;
        } else {
            return self::PORTRAIT;
        }
    }

    /**
     * @return bool
     */
    public function isLandscape()
    {
        return $this->getOrientation() == self::LANDSCAPE;
    }

    /**
     * @return bool
     */
    public function isPortrait()
    {
        return $this->getOrientation() == self::PORTRAIT;
    }
}
