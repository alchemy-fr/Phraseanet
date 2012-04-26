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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class metadata_description_H264_GPSLongitudeRef extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/H264:GPSLongitudeRef';
  const NAME_SPACE = 'H264';
  const TAGNAME = 'GPSLongitudeRef';
  const TYPE = self::TYPE_STRING;

  public static function available_values()
  {
    return array(
        'E' => 'East'
        , 'W' => 'West'
    );
  }

}
