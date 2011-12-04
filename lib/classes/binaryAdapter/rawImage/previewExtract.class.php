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
 * RawImage preview extractor adpater for binaryAdapter package
 *
 * @package     binaryAdapter
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class binaryAdapter_rawImage_previewExtract extends binaryAdapter_adapterAbstract
{

  protected $processors = array(
      'binaryAdapter_rawImage_previewExtract_exiftool'
  );

  public function get_name()
  {
    return 'Binary adapter rawImage preview Extractor';
  }

}
