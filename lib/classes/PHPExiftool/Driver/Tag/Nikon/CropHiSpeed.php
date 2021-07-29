<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CropHiSpeed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CropHiSpeed';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Crop Hi Speed';

    protected $flag_Permanent = true;

    protected $MaxLength = 7;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1.3x Crop',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'DX Crop',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '5:4 Crop',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '3:2 Crop',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '16:9 Crop',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '2.7x Crop',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'DX Movie Crop',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '1.3x Movie Crop',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'FX Uncropped',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'DX Uncropped',
        ),
        15 => array(
            'Id' => 15,
            'Label' => '1.5x Movie Crop',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '1:1 Crop',
        ),
    );

}
