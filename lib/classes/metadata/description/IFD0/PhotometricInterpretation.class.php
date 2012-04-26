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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class metadata_description_IFD0_PhotometricInterpretation extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/IFD0:PhotometricInterpretation';
  const NAME_SPACE = 'IFD0';
  const TAGNAME = 'PhotometricInterpretation';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_INT16U;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        '0' => 'WhiteIsZero'
        , '1' => 'BlackIsZero'
        , '2' => 'RGB'
        , '3' => 'RGB Palette'
        , '4' => 'Transparency Mask'
        , '5' => 'CMYK'
        , '6' => 'YCbCr'
        , '8' => 'CIELab'
        , '9' => 'ICCLab'
        , '10' => 'ITULab'
        , '32803' => 'Color Filter Array'
        , '32844' => 'Pixar LogL'
        , '32845' => 'Pixar LogLuv'
        , '34892' => 'Linear Raw'
    );
  }

}
