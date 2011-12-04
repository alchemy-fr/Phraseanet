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
class metadata_description_XMPphotoshop_Urgency extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-photoshop:Urgency';
  const NAME_SPACE = 'XMP-photoshop';
  const TAGNAME = 'Urgency';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_STRING;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '0' => '0 (reserved)'
        , '1' => '1 (most urgent)'
        , '2' => '2'
        , '3' => '3'
        , '4' => '4'
        , '5' => '5 (normal urgency)'
        , '6' => '6'
        , '7' => '7'
        , '8' => '8 (least urgent)'
        , '9' => '9 (user-defined priority)'
    );
  }

}
