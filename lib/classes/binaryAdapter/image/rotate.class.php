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
class binaryAdapter_image_rotate extends binaryAdapter_adapterAbstract
{

  protected $processors = array(
      'binaryAdapter_image_rotate_imagemagick'
      , 'binaryAdapter_image_rotate_gd'
  );

  public function get_name()
  {
    return 'Binary adapter image rotator';
  }

}
