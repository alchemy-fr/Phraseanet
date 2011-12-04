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
class metadata_description_XMPexif_GPSAltitudeRef extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-exif:GPSAltitudeRef';
  const NAME_SPACE = 'XMP-exif';
  const TAGNAME = 'GPSAltitudeRef';
  const TYPE = self::TYPE_INTEGER;

  public static function available_values()
  {
    return array(
        '0' => 'Above Sea Level'
        , '1' => 'Below Sea Level'
    );
  }

}
