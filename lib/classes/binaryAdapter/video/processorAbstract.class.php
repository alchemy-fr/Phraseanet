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
 * Abstract video adapter object for binaryAdapter
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class binaryAdapter_video_processorAbstract extends binaryAdapter_processorAbstract
{

  /**
   *
   * @param int $value
   * @param int $multiple
   * @param string $bound
   * @return int
   */
  protected function get_multiple($value, $multiple, $bound='nearest')
  {
    $modulo = $value % $multiple;

    $ret = 0;

    if ($bound == 'nearest')
    {
      $half_distance = $multiple / 2;
      if ($modulo <= $half_distance)
        $bound = 'bottom';
      else
        $bound = 'top';
    }

    switch ($bound)
    {
      default:
      case 'top':
        $ret = $value + $multiple - $modulo;
        break;
      case 'bottom':
        $ret = $value - $modulo;
        break;
    }

    if ($ret < $multiple)
      $ret = $multiple;

    return (int) $ret;
  }

  /**
   *
   * @param system_file $origine
   * @param int $size
   * @return array
   */
  protected function get_dimensions(system_file $origine, $size)
  {

    $tech_datas = $origine->get_technical_datas();

    $maxSize = $this->get_multiple($size, 16, 'bottom');

    $srcWidth = $tech_datas[system_file::TC_DATAS_WIDTH];
    $srcHeight = $tech_datas[system_file::TC_DATAS_HEIGHT];

    if ($srcWidth > $maxSize || $srcHeight > $maxSize)
    {
      if ($srcWidth >= $srcHeight)
      {
        $newWidth = (int) $maxSize;
        $height = round($newWidth * $srcHeight / $srcWidth);
        $newHeight = $this->get_multiple($height, 16);
      }
      else
      {
        $newHeight = (int) $maxSize;
        $width = round($newHeight * $srcWidth / $srcHeight);
        $newWidth = $this->get_multiple($width, 16);
      }
    }
    else
    {
      $newHeight = $this->get_multiple($srcHeight, 16);
      $newWidth = $this->get_multiple($srcWidth, 16);
    }

    return array('width' => $newWidth, 'height' => $newHeight);
  }

}
