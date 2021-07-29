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
class LensFocusFunctionButtons extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LensFocusFunctionButtons';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Lens Focus Function Buttons';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        1 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        2 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        3 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        4 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        5 => array(
            'Id' => 24,
            'Label' => 'Preset focus Point',
        ),
        6 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        7 => array(
            'Id' => 32,
            'Label' => 'AF-Area Mode:  Single-point AF',
        ),
        8 => array(
            'Id' => 33,
            'Label' => 'AF-Area Mode: Dynamic-area AF (9 points)',
        ),
        9 => array(
            'Id' => 34,
            'Label' => 'AF-Area Mode: Dynamic-area AF (21 points)',
        ),
        10 => array(
            'Id' => 35,
            'Label' => 'AF-Area Mode: Dynamic-area AF (51 points)',
        ),
        11 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode: Group-area AF',
        ),
        12 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode: Auto area AF',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        14 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        15 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        16 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        17 => array(
            'Id' => 24,
            'Label' => 'Preset Focus Point',
        ),
        18 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        19 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        20 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        21 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        22 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        23 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        24 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        25 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        26 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        27 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        28 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        29 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        30 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        31 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        32 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        33 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        34 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        35 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        36 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        37 => array(
            'Id' => 24,
            'Label' => 'Preset Focus Point',
        ),
        38 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        39 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        40 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        41 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        42 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        43 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        44 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        45 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        46 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        47 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        48 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        49 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        50 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        51 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        52 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        53 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        54 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        55 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        56 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        57 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        58 => array(
            'Id' => 24,
            'Label' => 'Preset focus Point',
        ),
        59 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        60 => array(
            'Id' => 32,
            'Label' => 'AF-Area Mode:  Single-point AF',
        ),
        61 => array(
            'Id' => 33,
            'Label' => 'AF-Area Mode: Dynamic-area AF (9 points)',
        ),
        62 => array(
            'Id' => 34,
            'Label' => 'AF-Area Mode: Dynamic-area AF (21 points)',
        ),
        63 => array(
            'Id' => 35,
            'Label' => 'AF-Area Mode: Dynamic-area AF (51 points)',
        ),
        64 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode: Group-area AF',
        ),
        65 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode: Auto area AF',
        ),
        66 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        67 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        68 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        69 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        70 => array(
            'Id' => 24,
            'Label' => 'Preset Focus Point',
        ),
        71 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        72 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        73 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        74 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        75 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 153 Points)',
        ),
        76 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        77 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        78 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        79 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        80 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        81 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 153 Points)',
        ),
        82 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        83 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        84 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        85 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        86 => array(
            'Id' => 56,
            'Label' => 'AF-Area Mode (Dynamic Area 9 Points)',
        ),
        87 => array(
            'Id' => 57,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 9 Points)',
        ),
    );

}
