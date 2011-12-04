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
 * Image Resize adpater for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_image_resize extends binaryAdapter_adapterAbstract
{

  protected $processors = array(
      'binaryAdapter_image_resize_sips',
      'binaryAdapter_image_resize_imagemagick',
      'binaryAdapter_image_resize_gd'
  );

  public function get_name()
  {
    return 'Binary adapter image resizer';
  }

}
