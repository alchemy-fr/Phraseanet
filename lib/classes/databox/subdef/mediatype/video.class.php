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
class databox_subdef_mediatype_video extends databox_subdef_mediatypeAbstract implements databox_subdef_mediatypeInterface
{

  /**
   *
   * @var int
   */
  protected $size;
  /**
   *
   * @var int
   */
  protected $fps;
  /**
   *
   * @var int
   */
  protected $threads;
  /**
   *
   * @var int
   */
  protected $bitrate;
  /**
   *
   * @var string
   */
  protected $v_codec;
  /**
   *
   * @var string
   */
  protected $a_codec;

  /**
   *
   * @param int $size
   * @param int $fps
   * @param int $threads
   * @param int $bitrate
   * @param string $acodec
   * @param string $vcodec
   * @return databox_subdef_mediatype_video
   */
  public function __construct($size=null, $fps=null, $threads=null, $bitrate=null, $acodec=null, $vcodec=null)
  {

    $this->size = $size;
    $this->fps = $fps;
    $this->threads = $threads;
    $this->bitrate = $bitrate;
    $this->a_codec = $acodec;
    $this->v_codec = $vcodec;

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
            'step' => 16,
            'min' => 100,
            'max' => 1000,
            'value' => $this->size,
            'default' => 600
        ),
        'fps' => array(
            'type' => 'range',
            'step' => 1,
            'min' => 1,
            'max' => 200,
            'value' => $this->fps,
            'default' => 20
        ),
        'threads' => array(
            'type' => 'range',
            'step' => 1,
            'min' => 1,
            'max' => 16,
            'value' => $this->threads,
            'default' => 1
        ),
        'bitrate' => array(
            'type' => 'range',
            'step' => 1,
            'min' => 100,
            'max' => 4000,
            'value' => $this->bitrate,
            'default' => '800'
        ),
        'a_codec' => array(
            'type' => 'enum',
            'values' => array('faac', 'mp3'),
            'value' => $this->a_codec,
            'default' => 'faac'
        ),
        'v_codec' => array(
            'type' => 'enum',
            'values' => array('libx264', 'flv'),
            'value' => $this->v_codec,
            'default' => 'libx264'
        )
    );

    return $options;
  }

}
