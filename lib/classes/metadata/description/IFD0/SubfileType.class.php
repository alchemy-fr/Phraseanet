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
class metadata_description_IFD0_SubfileType extends metadata_Abstract implements metadata_Interface
{
  const SOURCE = '/rdf:RDF/rdf:Description/IFD0:SubfileType';
  const NAME_SPACE = 'IFD0';
  const TAGNAME = 'SubfileType';
  const MAX_LENGTH = 0;
  const TYPE = self::TYPE_INT32U;
  const MANDATORY = false;
  const MULTI = false;
  const READONLY = false;

  public static function available_values()
  {
    return array(
        0x0 => 'Full-resolution Image'
        , 0x1 => 'Reduced-resolution image'
        , 0x2 => 'Single page of multi-page image'
        , 0x3 => 'Single page of multi-page reduced-resolution image'
        , 0x4 => 'Transparency mask'
        , 0x5 => 'Transparency mask of reduced-resolution image'
        , 0x6 => 'Transparency mask of multi-page image'
        , 0x7 => 'Transparency mask of reduced-resolution multi-page image'
        , 0xffffffff => 'invalid'
        , 'Bit 0' => 'Reduced resolution'
        , 'Bit 1' => 'Single page'
        , 'Bit 2' => 'Transparency mask'
        , 'Bit 3' => 'TIFF/IT final page'
        , 'Bit 4' => 'TIFF-FX mixed raster content'
    );
  }

}
