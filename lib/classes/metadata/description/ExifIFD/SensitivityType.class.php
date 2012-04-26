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
class metadata_description_ExifIFD_SensitivityType extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/ExifIFD:SensitivityType';
  const NAME_SPACE = 'ExifIFD';
  const TAGNAME = 'SensitivityType';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_INT16U;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '0' => 'Unknown'
        , '1' => 'Standard Output Sensitivity'
        , '2' => 'Recommended Exposure Index'
        , '3' => 'ISO Speed'
        , '4' => 'Standard Output Sensitivity and Recommended Exposure Index'
        , '5' => 'Standard Output Sensitivity and ISO Speed'
        , '6' => 'Recommended Exposure Index and ISO Speed'
        , '7' => 'Standard Output Sensitivity, Recommended Exposure Index and ISO Speed'
    );
  }

}
