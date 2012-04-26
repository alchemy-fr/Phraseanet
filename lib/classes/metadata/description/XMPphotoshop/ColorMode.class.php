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
class metadata_description_XMPphotoshop_ColorMode extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-photoshop:ColorMode';
  const NAME_SPACE = 'XMP-photoshop';
  const TAGNAME = 'ColorMode';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_STRING;

  public static function available_values()
  {
    return array(
        '0' => 'Bitmap'
        , '1' => 'Grayscale'
        , '2' => 'Indexed'
        , '3' => 'RGB'
        , '4' => 'CMYK'
        , '7' => 'Multichannel'
        , '8' => 'Duotone'
        , '9' => 'Lab'
    );
  }

}
