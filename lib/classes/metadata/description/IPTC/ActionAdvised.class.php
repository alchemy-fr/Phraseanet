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
class metadata_description_IPTC_ActionAdvised extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/IPTC:ActionAdvised';
  const NAME_SPACE = 'IPTC';
  const TAGNAME = 'ActionAdvised';
  const MAX_LENGTH = 2;
  const TYPE = self::TYPE_DIGITS;
  const MULTI = false;

  public static function available_values()
  {
    return array(
        '01' => 'Object Kill'
        , '02' => 'Object Replace'
        , '03' => 'Object Append'
        , '04' => 'Object Reference'
    );
  }

}

