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
class metadata_description_XMPcrs_ToneCurveName extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/XMP-crs:ToneCurveName';
  const NAME_SPACE = 'XMP-crs';
  const TAGNAME = 'ToneCurveName';
  const TYPE = self::TYPE_STRING;

  public static function available_values()
  {
    return array(
        'Custom' => 'Custom'
        , 'Linear' => 'Linear'
        , 'Medium Contrast' => 'Medium Contrast'
        , 'Strong Contrast' => 'Strong Contrast'
    );
  }

}
