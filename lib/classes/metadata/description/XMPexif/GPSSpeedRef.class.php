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
class metadata_description_XMPexif_GPSSpeedRef extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-exif:GPSSpeedRef';
  const NAME_SPACE = 'XMP-exif';
  const TAGNAME = 'GPSSpeedRef';
  const TYPE = self::TYPE_STRING;

  public static function available_values()
  {
    return array(
        'K' => 'km/h'
        , 'M' => 'mph'
        , 'N' => 'knots'
    );
  }

}
