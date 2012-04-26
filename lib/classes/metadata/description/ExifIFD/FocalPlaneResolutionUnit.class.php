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
class metadata_description_ExifIFD_FocalPlaneResolutionUnit extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/ExifIFD:FocalPlaneResolutionUnit';
  const NAME_SPACE = 'ExifIFD';
  const TAGNAME = 'FocalPlaneResolutionUnit';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_INT16U;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '1' => 'None'
        , '2' => 'inches'
        , '3' => 'cm'
        , '4' => 'mm'
        , '5' => 'um'
    );
  }

}
