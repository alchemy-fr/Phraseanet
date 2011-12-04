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
class metadata_description_XMPexif_SensingMethod extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-exif:SensingMethod';
  const NAME_SPACE = 'XMP-exif';
  const TAGNAME = 'SensingMethod';
  const TYPE = self::TYPE_INTEGER;

  public static function available_values()
  {
    return array(
        '1' => 'Monochrome area'
        , '2' => 'One-chip color area'
        , '3' => 'Two-chip color area'
        , '4' => 'Three-chip color area'
        , '5' => 'Color sequential area'
        , '6' => 'Monochrome linear'
        , '7' => 'Trilinear'
        , '8' => 'Color sequential linear'
    );
  }

}
