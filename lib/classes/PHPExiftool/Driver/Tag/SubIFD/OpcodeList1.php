<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\SubIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class OpcodeList1 extends AbstractTag
{

    protected $Id = 51008;

    protected $Name = 'OpcodeList1';

    protected $FullName = 'Exif::Main';

    protected $GroupName = 'SubIFD';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'undef';

    protected $Writable = true;

    protected $Description = 'Opcode List 1';

    protected $local_g1 = 'SubIFD';

    protected $flag_Binary = true;

    protected $flag_Unsafe = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'WarpRectilinear',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'WarpFisheye',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'FixVignetteRadial',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'FixBadPixelsConstant',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'FixBadPixelsList',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'TrimBounds',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'MapTable',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'MapPolynomial',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'GainMap',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'DeltaPerRow',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'DeltaPerColumn',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'ScalePerRow',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'ScalePerColumn',
        ),
    );

}
