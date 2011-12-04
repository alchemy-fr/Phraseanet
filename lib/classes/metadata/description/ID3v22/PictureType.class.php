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
class metadata_description_ID3v22_PictureType extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/ID3v2_2:PictureType';
  const NAME_SPACE = 'ID3v2_2';
  const TAGNAME = 'PictureType';
  const TYPE = self::TYPE_STRING;

  public static function available_values()
  {
    return array(
        0 => 'Other'
        , 1 => '32x32 PNG Icon'
        , 2 => 'Other Icon'
        , 3 => 'Front Cover'
        , 4 => 'Back Cover'
        , 5 => 'Leaflet'
        , 6 => 'Media'
        , 7 => 'Lead Artist'
        , 8 => 'Artist'
        , 9 => 'Conductor'
        , 10 => 'Band'
        , 11 => 'Composer'
        , 12 => 'Lyricist'
        , 13 => 'Recording Studio or Location'
        , 14 => 'Recording Session'
        , 15 => 'Performance'
        , 16 => 'Capture from Movie or Video'
        , 17 => 'Bright(ly) Colored Fish'
        , 18 => 'Illustration'
        , 19 => 'Band Logo'
        , 20 => 'Publisher Logo'
    );
  }

}
