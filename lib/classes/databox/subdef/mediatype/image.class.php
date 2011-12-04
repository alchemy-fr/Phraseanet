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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class databox_subdef_mediatype_image extends databox_subdef_mediatypeAbstract implements databox_subdef_mediatypeInterface
{

  /**
   *
   * @var integer
   */
  protected $size;
  /**
   *
   * @var integer
   */
  protected $resolution;
  /**
   *
   * @var boolean
   */
  protected $strip;
  /**
   *
   * @var integer
   */
  protected $quality;

  /**
   *
   * @param int $size
   * @param int $resolution
   * @param boolean $strip
   * @param int $quality
   * @return databox_subdef_mediatype_image
   */
  public function __construct($size=null, $resolution=null, $strip=null, $quality=null)
  {

    $this->size = $size;
    $this->resolution = $resolution;
    $this->strip = $strip;
    $this->quality = $quality;

    return $this;
  }

  /**
   *
   * @param databox_subdefAbstract $subdef
   * @return array
   */
  public function get_available_options(databox_subdefAbstract $subdef)
  {

    $options = array(
        'size' => array(
            'type' => 'range',
            'step' => 1,
            'min' => ($subdef->get_class() == 'thumbnail' ? 20 : 150),
            'max' => ($subdef->get_class() == 'thumbnail' ? 400 : 3000),
            'default' => ($subdef->get_class() == 'thumbnail' ? 200 : 800),
            'value' => $this->size
        ),
        'resolution' => array(
            'type' => 'range',
            'step' => 1,
            'min' => 50,
            'max' => 300,
            'default' => 72,
            'value' => $this->resolution
        ),
        'strip' => array(
            'type' => 'boolean',
            'default' => ($subdef->get_class() == 'thumbnail'),
            'value' => $this->strip
        ),
        'quality' => array(
            'type' => 'range',
            'step' => 1,
            'min' => 0,
            'max' => 100,
            'default' => 75,
            'value' => $this->quality
        )
    );

    return $options;
  }

}
