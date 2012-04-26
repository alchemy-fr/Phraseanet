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
class metadata_description_XMPexif_GainControl extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-exif:GainControl';
  const NAME_SPACE = 'XMP-exif';
  const TAGNAME = 'GainControl';
  const TYPE = self::TYPE_INTEGER;

  public static function available_values()
  {
    return array(
        '0' => 'None'
        , '1' => 'Low gain up'
        , '2' => 'High gain up'
        , '3' => 'Low gain down'
        , '4' => 'High gain down'
    );
  }

}
