<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Pentax;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPointSelected extends AbstractTag
{

    protected $Id = 14;

    protected $Name = 'AFPointSelected';

    protected $FullName = 'Pentax::Main';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'AF Point Selected';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Top-left',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Top Near-left',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Top',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Top Near-right',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Top-right',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Upper Far-left',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Upper-left',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Upper Near-left',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Upper-middle',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Upper Near-right',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Upper-right',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Upper Far-right',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Far Far Left',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Far Left',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Left',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'Near-left',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'Center',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'Near-right',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'Right',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'Far Right',
        ),
        21 => array(
            'Id' => 21,
            'Label' => 'Far Far Right',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Lower Far-left',
        ),
        23 => array(
            'Id' => 23,
            'Label' => 'Lower-left',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Lower Near-left',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Lower-middle',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Lower Near-right',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Lower-right',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Lower Far-right',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Bottom-left',
        ),
        30 => array(
            'Id' => 30,
            'Label' => 'Bottom Near-left',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Bottom',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Bottom Near-right',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Bottom-right',
        ),
        34 => array(
            'Id' => 263,
            'Label' => 'Zone Select Upper-left',
        ),
        35 => array(
            'Id' => 264,
            'Label' => 'Zone Select Upper Near-left',
        ),
        36 => array(
            'Id' => 265,
            'Label' => 'Zone Select Upper Middle',
        ),
        37 => array(
            'Id' => 266,
            'Label' => 'Zone Select Upper Near-right',
        ),
        38 => array(
            'Id' => 267,
            'Label' => 'Zone Select Upper-right',
        ),
        39 => array(
            'Id' => 270,
            'Label' => 'Zone Select Far Left',
        ),
        40 => array(
            'Id' => 271,
            'Label' => 'Zone Select Left',
        ),
        41 => array(
            'Id' => 272,
            'Label' => 'Zone Select Near-left',
        ),
        42 => array(
            'Id' => 273,
            'Label' => 'Zone Select Center',
        ),
        43 => array(
            'Id' => 274,
            'Label' => 'Zone Select Near-right',
        ),
        44 => array(
            'Id' => 275,
            'Label' => 'Zone Select Right',
        ),
        45 => array(
            'Id' => 276,
            'Label' => 'Zone Select Far Right',
        ),
        46 => array(
            'Id' => 279,
            'Label' => 'Zone Select Lower-left',
        ),
        47 => array(
            'Id' => 280,
            'Label' => 'Zone Select Lower Near-left',
        ),
        48 => array(
            'Id' => 281,
            'Label' => 'Zone Select Lower-middle',
        ),
        49 => array(
            'Id' => 282,
            'Label' => 'Zone Select Lower Near-right',
        ),
        50 => array(
            'Id' => 283,
            'Label' => 'Zone Select Lower-right',
        ),
        51 => array(
            'Id' => 65531,
            'Label' => 'AF Select',
        ),
        52 => array(
            'Id' => 65532,
            'Label' => 'Face Detect AF',
        ),
        53 => array(
            'Id' => 65533,
            'Label' => 'Automatic Tracking AF',
        ),
        54 => array(
            'Id' => 65534,
            'Label' => 'Fixed Center',
        ),
        55 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
        56 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        57 => array(
            'Id' => 1,
            'Label' => 'Top-left',
        ),
        58 => array(
            'Id' => 2,
            'Label' => 'Top Near-left',
        ),
        59 => array(
            'Id' => 3,
            'Label' => 'Top',
        ),
        60 => array(
            'Id' => 4,
            'Label' => 'Top Near-right',
        ),
        61 => array(
            'Id' => 5,
            'Label' => 'Top-right',
        ),
        62 => array(
            'Id' => 6,
            'Label' => 'Upper-left',
        ),
        63 => array(
            'Id' => 7,
            'Label' => 'Upper Near-left',
        ),
        64 => array(
            'Id' => 8,
            'Label' => 'Upper-middle',
        ),
        65 => array(
            'Id' => 9,
            'Label' => 'Upper Near-right',
        ),
        66 => array(
            'Id' => 10,
            'Label' => 'Upper-right',
        ),
        67 => array(
            'Id' => 11,
            'Label' => 'Far Left',
        ),
        68 => array(
            'Id' => 12,
            'Label' => 'Left',
        ),
        69 => array(
            'Id' => 13,
            'Label' => 'Near-left',
        ),
        70 => array(
            'Id' => 14,
            'Label' => 'Center',
        ),
        71 => array(
            'Id' => 15,
            'Label' => 'Near-right',
        ),
        72 => array(
            'Id' => 16,
            'Label' => 'Right',
        ),
        73 => array(
            'Id' => 17,
            'Label' => 'Far Right',
        ),
        74 => array(
            'Id' => 18,
            'Label' => 'Lower-left',
        ),
        75 => array(
            'Id' => 19,
            'Label' => 'Lower Near-left',
        ),
        76 => array(
            'Id' => 20,
            'Label' => 'Lower-middle',
        ),
        77 => array(
            'Id' => 21,
            'Label' => 'Lower Near-right',
        ),
        78 => array(
            'Id' => 22,
            'Label' => 'Lower-right',
        ),
        79 => array(
            'Id' => 23,
            'Label' => 'Bottom-left',
        ),
        80 => array(
            'Id' => 24,
            'Label' => 'Bottom Near-left',
        ),
        81 => array(
            'Id' => 25,
            'Label' => 'Bottom',
        ),
        82 => array(
            'Id' => 26,
            'Label' => 'Bottom Near-right',
        ),
        83 => array(
            'Id' => 27,
            'Label' => 'Bottom-right',
        ),
        84 => array(
            'Id' => 257,
            'Label' => 'Zone Select Top-left',
        ),
        85 => array(
            'Id' => 258,
            'Label' => 'Zone Select Top Near-left',
        ),
        86 => array(
            'Id' => 259,
            'Label' => 'Zone Select Top',
        ),
        87 => array(
            'Id' => 260,
            'Label' => 'Zone Select Top Near-right',
        ),
        88 => array(
            'Id' => 261,
            'Label' => 'Zone Select Top-right',
        ),
        89 => array(
            'Id' => 262,
            'Label' => 'Zone Select Upper-left',
        ),
        90 => array(
            'Id' => 263,
            'Label' => 'Zone Select Upper Near-left',
        ),
        91 => array(
            'Id' => 264,
            'Label' => 'Zone Select Upper-middle',
        ),
        92 => array(
            'Id' => 265,
            'Label' => 'Zone Select Upper Near-right',
        ),
        93 => array(
            'Id' => 266,
            'Label' => 'Zone Select Upper-right',
        ),
        94 => array(
            'Id' => 267,
            'Label' => 'Zone Select Far Left',
        ),
        95 => array(
            'Id' => 268,
            'Label' => 'Zone Select Left',
        ),
        96 => array(
            'Id' => 269,
            'Label' => 'Zone Select Near-left',
        ),
        97 => array(
            'Id' => 270,
            'Label' => 'Zone Select Center',
        ),
        98 => array(
            'Id' => 271,
            'Label' => 'Zone Select Near-right',
        ),
        99 => array(
            'Id' => 272,
            'Label' => 'Zone Select Right',
        ),
        100 => array(
            'Id' => 273,
            'Label' => 'Zone Select Far Right',
        ),
        101 => array(
            'Id' => 274,
            'Label' => 'Zone Select Lower-left',
        ),
        102 => array(
            'Id' => 275,
            'Label' => 'Zone Select Lower Near-left',
        ),
        103 => array(
            'Id' => 276,
            'Label' => 'Zone Select Lower-middle',
        ),
        104 => array(
            'Id' => 277,
            'Label' => 'Zone Select Lower Near-right',
        ),
        105 => array(
            'Id' => 278,
            'Label' => 'Zone Select Lower-right',
        ),
        106 => array(
            'Id' => 279,
            'Label' => 'Zone Select Bottom-left',
        ),
        107 => array(
            'Id' => 280,
            'Label' => 'Zone Select Bottom Near-left',
        ),
        108 => array(
            'Id' => 281,
            'Label' => 'Zone Select Bottom',
        ),
        109 => array(
            'Id' => 282,
            'Label' => 'Zone Select Bottom Near-right',
        ),
        110 => array(
            'Id' => 283,
            'Label' => 'Zone Select Bottom-right',
        ),
        111 => array(
            'Id' => 65531,
            'Label' => 'AF Select',
        ),
        112 => array(
            'Id' => 65532,
            'Label' => 'Face Detect AF',
        ),
        113 => array(
            'Id' => 65533,
            'Label' => 'Automatic Tracking AF',
        ),
        114 => array(
            'Id' => 65534,
            'Label' => 'Fixed Center',
        ),
        115 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
        116 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        117 => array(
            'Id' => 1,
            'Label' => 'Upper-left',
        ),
        118 => array(
            'Id' => 2,
            'Label' => 'Top',
        ),
        119 => array(
            'Id' => 3,
            'Label' => 'Upper-right',
        ),
        120 => array(
            'Id' => 4,
            'Label' => 'Left',
        ),
        121 => array(
            'Id' => 5,
            'Label' => 'Mid-left',
        ),
        122 => array(
            'Id' => 6,
            'Label' => 'Center',
        ),
        123 => array(
            'Id' => 7,
            'Label' => 'Mid-right',
        ),
        124 => array(
            'Id' => 8,
            'Label' => 'Right',
        ),
        125 => array(
            'Id' => 9,
            'Label' => 'Lower-left',
        ),
        126 => array(
            'Id' => 10,
            'Label' => 'Bottom',
        ),
        127 => array(
            'Id' => 11,
            'Label' => 'Lower-right',
        ),
        128 => array(
            'Id' => 65531,
            'Label' => 'AF Select',
        ),
        129 => array(
            'Id' => 65532,
            'Label' => 'Face Detect AF',
        ),
        130 => array(
            'Id' => 65533,
            'Label' => 'Automatic Tracking AF',
        ),
        131 => array(
            'Id' => 65534,
            'Label' => 'Fixed Center',
        ),
        132 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
    );

    protected $Index = 'mixed';

}
