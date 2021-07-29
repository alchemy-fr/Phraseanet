<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\File;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ColorSpace extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ColorSpace';

    protected $FullName = 'mixed';

    protected $GroupName = 'File';

    protected $g0 = 'File';

    protected $g1 = 'File';

    protected $g2 = 'Image';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'Color Space';

    protected $MaxLength = 4;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Calibrated RGB',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Device RGB',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Device CMYK',
        ),
        'LINK' => array(
            'Id' => 'LINK',
            'Label' => 'Linked Color Profile',
        ),
        'MBED' => array(
            'Id' => 'MBED',
            'Label' => 'Embedded Color Profile',
        ),
        'Win ' => array(
            'Id' => 'Win ',
            'Label' => 'Windows Color Space',
        ),
        'sRGB' => array(
            'Id' => 'sRGB',
            'Label' => 'sRGB',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'YCbCr (BT 601)',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'RGB',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'YCgCo',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'YCbCr (BT 709)',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'YCbCr (BT 2020)',
        ),
        8 => array(
            'Id' => 5,
            'Label' => 'BT 2020 Constant Luminance',
        ),
    );

}
