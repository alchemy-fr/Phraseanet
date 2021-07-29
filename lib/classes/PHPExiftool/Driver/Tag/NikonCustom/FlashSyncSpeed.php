<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FlashSyncSpeed extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'FlashSyncSpeed';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Flash Sync Speed';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1/250 s (auto FP)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1/250 s',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1/200 s',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1/160 s',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '1/125 s',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '1/100 s',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '1/80 s',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '1/60 s',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        9 => array(
            'Id' => 1,
            'Label' => '1/250 s (auto FP)',
        ),
        10 => array(
            'Id' => 2,
            'Label' => '1/250 s',
        ),
        11 => array(
            'Id' => 3,
            'Label' => '1/200 s',
        ),
        12 => array(
            'Id' => 4,
            'Label' => '1/160 s',
        ),
        13 => array(
            'Id' => 5,
            'Label' => '1/125 s',
        ),
        14 => array(
            'Id' => 6,
            'Label' => '1/100 s',
        ),
        15 => array(
            'Id' => 7,
            'Label' => '1/80 s',
        ),
        16 => array(
            'Id' => 8,
            'Label' => '1/60 s',
        ),
        17 => array(
            'Id' => 1,
            'Label' => '1/250 s (auto FP)',
        ),
        18 => array(
            'Id' => 2,
            'Label' => '1/250 s',
        ),
        19 => array(
            'Id' => 3,
            'Label' => '1/200 s',
        ),
        20 => array(
            'Id' => 4,
            'Label' => '1/160 s',
        ),
        21 => array(
            'Id' => 5,
            'Label' => '1/125 s',
        ),
        22 => array(
            'Id' => 6,
            'Label' => '1/100 s',
        ),
        23 => array(
            'Id' => 7,
            'Label' => '1/80 s',
        ),
        24 => array(
            'Id' => 8,
            'Label' => '1/60 s',
        ),
        25 => array(
            'Id' => 2,
            'Label' => '1/250 s (auto FP)',
        ),
        26 => array(
            'Id' => 3,
            'Label' => '1/250 s',
        ),
        27 => array(
            'Id' => 5,
            'Label' => '1/200 s',
        ),
        28 => array(
            'Id' => 6,
            'Label' => '1/160 s',
        ),
        29 => array(
            'Id' => 7,
            'Label' => '1/125 s',
        ),
        30 => array(
            'Id' => 8,
            'Label' => '1/100 s',
        ),
        31 => array(
            'Id' => 9,
            'Label' => '1/80 s',
        ),
        32 => array(
            'Id' => 10,
            'Label' => '1/60 s',
        ),
        33 => array(
            'Id' => 2,
            'Label' => '1/250 s (auto FP)',
        ),
        34 => array(
            'Id' => 3,
            'Label' => '1/250 s',
        ),
        35 => array(
            'Id' => 5,
            'Label' => '1/200 s',
        ),
        36 => array(
            'Id' => 6,
            'Label' => '1/160 s',
        ),
        37 => array(
            'Id' => 7,
            'Label' => '1/125 s',
        ),
        38 => array(
            'Id' => 8,
            'Label' => '1/100 s',
        ),
        39 => array(
            'Id' => 9,
            'Label' => '1/80 s',
        ),
        40 => array(
            'Id' => 10,
            'Label' => '1/60 s',
        ),
        41 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        42 => array(
            'Id' => 1,
            'Label' => '1/250 s (auto FP)',
        ),
        43 => array(
            'Id' => 2,
            'Label' => '1/250 s',
        ),
        44 => array(
            'Id' => 3,
            'Label' => '1/200 s',
        ),
        45 => array(
            'Id' => 4,
            'Label' => '1/160 s',
        ),
        46 => array(
            'Id' => 5,
            'Label' => '1/125 s',
        ),
        47 => array(
            'Id' => 6,
            'Label' => '1/100 s',
        ),
        48 => array(
            'Id' => 7,
            'Label' => '1/80 s',
        ),
        49 => array(
            'Id' => 8,
            'Label' => '1/60 s',
        ),
        50 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        51 => array(
            'Id' => 1,
            'Label' => '1/250 s (auto FP)',
        ),
        52 => array(
            'Id' => 2,
            'Label' => '1/250 s',
        ),
        53 => array(
            'Id' => 3,
            'Label' => '1/200 s',
        ),
        54 => array(
            'Id' => 4,
            'Label' => '1/160 s',
        ),
        55 => array(
            'Id' => 5,
            'Label' => '1/125 s',
        ),
        56 => array(
            'Id' => 6,
            'Label' => '1/100 s',
        ),
        57 => array(
            'Id' => 7,
            'Label' => '1/80 s',
        ),
        58 => array(
            'Id' => 8,
            'Label' => '1/60 s',
        ),
        59 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        60 => array(
            'Id' => 1,
            'Label' => '1/250 s (auto FP)',
        ),
        61 => array(
            'Id' => 2,
            'Label' => '1/250 s',
        ),
        62 => array(
            'Id' => 3,
            'Label' => '1/200 s',
        ),
        63 => array(
            'Id' => 4,
            'Label' => '1/160 s',
        ),
        64 => array(
            'Id' => 5,
            'Label' => '1/125 s',
        ),
        65 => array(
            'Id' => 6,
            'Label' => '1/100 s',
        ),
        66 => array(
            'Id' => 7,
            'Label' => '1/80 s',
        ),
        67 => array(
            'Id' => 8,
            'Label' => '1/60 s',
        ),
        68 => array(
            'Id' => 0,
            'Label' => '1/320 s (auto FP)',
        ),
        69 => array(
            'Id' => 2,
            'Label' => '1/250 s (auto FP)',
        ),
        70 => array(
            'Id' => 3,
            'Label' => '1/250 s',
        ),
        71 => array(
            'Id' => 5,
            'Label' => '1/200 s',
        ),
        72 => array(
            'Id' => 6,
            'Label' => '1/160 s',
        ),
        73 => array(
            'Id' => 7,
            'Label' => '1/125 s',
        ),
        74 => array(
            'Id' => 8,
            'Label' => '1/100 s',
        ),
        75 => array(
            'Id' => 9,
            'Label' => '1/80 s',
        ),
        76 => array(
            'Id' => 10,
            'Label' => '1/60 s',
        ),
        77 => array(
            'Id' => 2,
            'Label' => '1/250 s (auto FP)',
        ),
        78 => array(
            'Id' => 3,
            'Label' => '1/250 s',
        ),
        79 => array(
            'Id' => 5,
            'Label' => '1/200 s',
        ),
        80 => array(
            'Id' => 6,
            'Label' => '1/160 s',
        ),
        81 => array(
            'Id' => 7,
            'Label' => '1/125 s',
        ),
        82 => array(
            'Id' => 8,
            'Label' => '1/100 s',
        ),
        83 => array(
            'Id' => 9,
            'Label' => '1/80 s',
        ),
        84 => array(
            'Id' => 10,
            'Label' => '1/60 s',
        ),
    );

    protected $Index = 'mixed';

}
