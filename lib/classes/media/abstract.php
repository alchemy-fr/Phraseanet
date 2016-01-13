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
    const PAYSAGE = 'LANDSCAPE';

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
     * @param Url $url
     * @param int $width
     * @param int $height
     */
    public function __construct(Url $url, $width, $height)
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
    public function get_orientation()
    {
        if ($this->width > $this->height) {
            return self::PAYSAGE;
        } else {
            return self::PORTRAIT;
        }
    }

    /**
     * @return bool
     */
    public function is_paysage()
    {
        return $this->get_orientation() == self::PAYSAGE;
    }

    /**
     * @return bool
     */
    public function is_portrait()
    {
        return $this->get_orientation() == self::PORTRAIT;
    }
}
