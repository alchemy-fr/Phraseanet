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
class metadata_description_XMPexif_ComponentsConfiguration extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-exif:ComponentsConfiguration';
  const NAME_SPACE = 'XMP-exif';
  const TAGNAME = 'ComponentsConfiguration';
  const TYPE = self::TYPE_INTEGER;
  const MULTI = true;

  public static function available_values()
  {
    return array(
        '0' => '-'
        , '1' => 'Y'
        , '2' => 'Cb'
        , '3' => 'Cr'
        , '4' => 'R'
        , '5' => 'G'
        , '6' => 'B'
    );
  }

}
