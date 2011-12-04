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
class databox_subdef_mediatype_gif extends databox_subdef_mediatypeAbstract implements databox_subdef_mediatypeInterface
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
  protected $delay;

  /**
   *
   * @param int $size
   * @param int $delay
   * @return databox_subdef_mediatype_gif
   */
  public function __construct($size=null, $delay=null)
  {
    $this->size = $size;
    $this->delay = $delay;

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
            'min' => 20,
            'max' => 500,
            'value' => $this->size,
            'default' => 200
        ),
        'delay' => array(
            'type' => 'range',
            'step' => 1,
            'min' => 1,
            'max' => 3,
            'value' => $this->delay,
            'default' => 1
        )
    );

    return $options;
  }

}
