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
class metadata_description_H264_ExposureProgram extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/H264:ExposureProgram';
  const NAME_SPACE = 'H264';
  const TAGNAME = 'ExposureProgram';
  const TYPE = self::TYPE_STRING;

  public static function available_values()
  {
    return array(
        '0' => 'Not Defined'
        , '1' => 'Manual'
        , '2' => 'Program AE'
        , '3' => 'Aperture-priority AE'
        , '4' => 'Shutter speed priority AE'
        , '5' => 'Creative (Slow speed)'
        , '6' => 'Action (High speed)'
        , '7' => 'Portrait'
        , '8' => 'Landscape'
    );
  }

}
