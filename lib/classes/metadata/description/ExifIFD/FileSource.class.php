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
class metadata_description_ExifIFD_FileSource extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/ExifIFD:FileSource';
  const NAME_SPACE = 'ExifIFD';
  const TAGNAME = 'FileSource';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_BINARY;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '1' => 'Film Scanner'
        , '2' => 'Reflection Print Scanner'
        , '3' => 'Digital Camera'
        , '"\x03\x00\x00\x00"' => 'Sigma Digital Camera'
    );
  }

}
