<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class metadata_description_XMPcrs_CropUnits extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-crs:CropUnits';
  const NAME_SPACE = 'XMP-crs';
  const TAGNAME = 'CropUnits';
  const TYPE = self::TYPE_INTEGER;

  public static function available_values()
  {
    return array(
        '0' => 'pixels'
        , '1' => 'inches'
        , '2' => 'cm'
    );
  }

}
