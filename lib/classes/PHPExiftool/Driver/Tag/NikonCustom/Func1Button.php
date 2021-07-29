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
class Func1Button extends AbstractTag
{

    protected $Id = '14.1';

    protected $Name = 'Func1Button';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Func 1 Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        9 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        10 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        11 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        12 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        13 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        14 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        15 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        16 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        17 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        18 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        19 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        20 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        21 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        22 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        23 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        24 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        25 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        26 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        27 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        28 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        29 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        30 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        31 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        32 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        33 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        34 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        35 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        36 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        37 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        38 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        39 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        40 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        41 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        42 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        43 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        44 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        45 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        46 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        47 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        48 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        49 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        50 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        51 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        52 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        53 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        54 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        55 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        56 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        57 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        58 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        59 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        60 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        61 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        62 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        63 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        64 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        65 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        66 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        67 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        68 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        69 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        70 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        71 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        72 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        73 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        74 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        75 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        76 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        77 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        78 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        79 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        80 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        81 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        82 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        83 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        84 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        85 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        86 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        87 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        88 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        89 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        90 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        91 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        92 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        93 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        94 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        95 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        96 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        97 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        98 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 153 Points)',
        ),
        99 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        100 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        101 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        102 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        103 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        104 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 153 Points)',
        ),
        105 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        106 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        107 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        108 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        109 => array(
            'Id' => 56,
            'Label' => 'AF-Area Mode (Dynamic Area 9 Points)',
        ),
        110 => array(
            'Id' => 57,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 9 Points)',
        ),
    );

}
