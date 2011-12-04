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
class metadata_description_IPTC_Urgency extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/IPTC:Urgency';
  const NAME_SPACE = 'IPTC';
  const TAGNAME = 'Urgency';
  const MAX_LENGTH = 1;
  const TYPE = self::TYPE_DIGITS;
  const MULTI = false;

  public static function available_values()
  {
    return array(
        '0' => 'reserved'
        , '1' => 'most urgent'
        , '2' => ''
        , '3' => ''
        , '4' => ''
        , '5' => 'normal urgency'
        , '6' => ''
        , '7' => ''
        , '8' => 'least urgent'
        , '9' => 'user-defined priority'
    );
  }

}

