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
class ISO extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ISO';

    protected $FullName = 'mixed';

    protected $GroupName = 'Pentax';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Pentax';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'ISO';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 3,
            'Label' => 50,
        ),
        1 => array(
            'Id' => 4,
            'Label' => 64,
        ),
        2 => array(
            'Id' => 5,
            'Label' => 80,
        ),
        3 => array(
            'Id' => 6,
            'Label' => 100,
        ),
        4 => array(
            'Id' => 7,
            'Label' => 125,
        ),
        5 => array(
            'Id' => 8,
            'Label' => 160,
        ),
        6 => array(
            'Id' => 9,
            'Label' => 200,
        ),
        7 => array(
            'Id' => 10,
            'Label' => 250,
        ),
        8 => array(
            'Id' => 11,
            'Label' => 320,
        ),
        9 => array(
            'Id' => 12,
            'Label' => 400,
        ),
        10 => array(
            'Id' => 13,
            'Label' => 500,
        ),
        11 => array(
            'Id' => 14,
            'Label' => 640,
        ),
        12 => array(
            'Id' => 15,
            'Label' => 800,
        ),
        13 => array(
            'Id' => 16,
            'Label' => 1000,
        ),
        14 => array(
            'Id' => 17,
            'Label' => 1250,
        ),
        15 => array(
            'Id' => 18,
            'Label' => 1600,
        ),
        16 => array(
            'Id' => 19,
            'Label' => 2000,
        ),
        17 => array(
            'Id' => 20,
            'Label' => 2500,
        ),
        18 => array(
            'Id' => 21,
            'Label' => 3200,
        ),
        19 => array(
            'Id' => 22,
            'Label' => 4000,
        ),
        20 => array(
            'Id' => 23,
            'Label' => 5000,
        ),
        21 => array(
            'Id' => 24,
            'Label' => 6400,
        ),
        22 => array(
            'Id' => 25,
            'Label' => 8000,
        ),
        23 => array(
            'Id' => 26,
            'Label' => 10000,
        ),
        24 => array(
            'Id' => 27,
            'Label' => 12800,
        ),
        25 => array(
            'Id' => 28,
            'Label' => 16000,
        ),
        26 => array(
            'Id' => 29,
            'Label' => 20000,
        ),
        27 => array(
            'Id' => 30,
            'Label' => 25600,
        ),
        28 => array(
            'Id' => 31,
            'Label' => 32000,
        ),
        29 => array(
            'Id' => 32,
            'Label' => 40000,
        ),
        30 => array(
            'Id' => 33,
            'Label' => 51200,
        ),
        31 => array(
            'Id' => 34,
            'Label' => 64000,
        ),
        32 => array(
            'Id' => 35,
            'Label' => 80000,
        ),
        33 => array(
            'Id' => 36,
            'Label' => 102400,
        ),
        34 => array(
            'Id' => 37,
            'Label' => 128000,
        ),
        35 => array(
            'Id' => 38,
            'Label' => 160000,
        ),
        36 => array(
            'Id' => 39,
            'Label' => 204800,
        ),
        37 => array(
            'Id' => 40,
            'Label' => 256000,
        ),
        38 => array(
            'Id' => 41,
            'Label' => 320000,
        ),
        39 => array(
            'Id' => 42,
            'Label' => 409600,
        ),
        40 => array(
            'Id' => 43,
            'Label' => 512000,
        ),
        41 => array(
            'Id' => 44,
            'Label' => 640000,
        ),
        42 => array(
            'Id' => 45,
            'Label' => 819200,
        ),
        43 => array(
            'Id' => 50,
            'Label' => 50,
        ),
        44 => array(
            'Id' => 100,
            'Label' => 100,
        ),
        45 => array(
            'Id' => 200,
            'Label' => 200,
        ),
        46 => array(
            'Id' => 258,
            'Label' => 50,
        ),
        47 => array(
            'Id' => 259,
            'Label' => 70,
        ),
        48 => array(
            'Id' => 260,
            'Label' => 100,
        ),
        49 => array(
            'Id' => 261,
            'Label' => 140,
        ),
        50 => array(
            'Id' => 262,
            'Label' => 200,
        ),
        51 => array(
            'Id' => 263,
            'Label' => 280,
        ),
        52 => array(
            'Id' => 264,
            'Label' => 400,
        ),
        53 => array(
            'Id' => 265,
            'Label' => 560,
        ),
        54 => array(
            'Id' => 266,
            'Label' => 800,
        ),
        55 => array(
            'Id' => 267,
            'Label' => 1100,
        ),
        56 => array(
            'Id' => 268,
            'Label' => 1600,
        ),
        57 => array(
            'Id' => 269,
            'Label' => 2200,
        ),
        58 => array(
            'Id' => 270,
            'Label' => 3200,
        ),
        59 => array(
            'Id' => 271,
            'Label' => 4500,
        ),
        60 => array(
            'Id' => 272,
            'Label' => 6400,
        ),
        61 => array(
            'Id' => 273,
            'Label' => 9000,
        ),
        62 => array(
            'Id' => 274,
            'Label' => 12800,
        ),
        63 => array(
            'Id' => 275,
            'Label' => 18000,
        ),
        64 => array(
            'Id' => 276,
            'Label' => 25600,
        ),
        65 => array(
            'Id' => 277,
            'Label' => 36000,
        ),
        66 => array(
            'Id' => 278,
            'Label' => 51200,
        ),
        67 => array(
            'Id' => 279,
            'Label' => 72000,
        ),
        68 => array(
            'Id' => 280,
            'Label' => 102400,
        ),
        69 => array(
            'Id' => 281,
            'Label' => 144000,
        ),
        70 => array(
            'Id' => 282,
            'Label' => 204800,
        ),
        71 => array(
            'Id' => 283,
            'Label' => 288000,
        ),
        72 => array(
            'Id' => 284,
            'Label' => 409600,
        ),
        73 => array(
            'Id' => 285,
            'Label' => 576000,
        ),
        74 => array(
            'Id' => 286,
            'Label' => 819200,
        ),
        75 => array(
            'Id' => 400,
            'Label' => 400,
        ),
        76 => array(
            'Id' => 800,
            'Label' => 800,
        ),
        77 => array(
            'Id' => 1600,
            'Label' => 1600,
        ),
        78 => array(
            'Id' => 3200,
            'Label' => 3200,
        ),
        79 => array(
            'Id' => 65534,
            'Label' => 'Auto 2',
        ),
        80 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
        81 => array(
            'Id' => 10,
            'Label' => 100,
        ),
        82 => array(
            'Id' => 16,
            'Label' => 200,
        ),
        83 => array(
            'Id' => 50,
            'Label' => 50,
        ),
        84 => array(
            'Id' => 100,
            'Label' => 100,
        ),
        85 => array(
            'Id' => 200,
            'Label' => 200,
        ),
        86 => array(
            'Id' => 400,
            'Label' => 400,
        ),
        87 => array(
            'Id' => 800,
            'Label' => 800,
        ),
        88 => array(
            'Id' => 1600,
            'Label' => 1600,
        ),
        89 => array(
            'Id' => 3200,
            'Label' => 3200,
        ),
        90 => array(
            'Id' => 65534,
            'Label' => 'Auto 2',
        ),
        91 => array(
            'Id' => 65535,
            'Label' => 'Auto',
        ),
    );

}
