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
class metadata_description_IPTC_AudioType extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/IPTC:AudioType';
  const NAME_SPACE = 'IPTC';
  const TAGNAME = 'AudioType';
  const MAX_LENGTH = 2;
  const TYPE = self::TYPE_STRING;
  const MULTI = false;

  public static function available_values()
  {
    return array(
        '0T' => 'Text Only'
        , '1A' => 'Mono Actuality'
        , '1C' => 'Mono Question and Answer Session'
        , '1M' => 'Mono Music'
        , '1Q' => 'Mono Response to a Question'
        , '1R' => 'Mono Raw Sound'
        , '1S' => 'Mono Scener'
        , '1V' => 'Mono Voicer'
        , '1W' => 'Mono Wrap'
        , '2A' => 'Stereo Actuality'
        , '2C' => 'Stereo Question and Answer Session'
        , '2M' => 'Stereo Music'
        , '2Q' => 'Stereo Response to a Question'
        , '2R' => 'Stereo Raw Sound'
        , '2S' => 'Stereo Scener'
        , '2V' => 'Stereo Voicer'
        , '2W' => 'Stereo Wrap'
    );
  }

}

