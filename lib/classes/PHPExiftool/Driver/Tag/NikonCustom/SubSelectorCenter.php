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
class SubSelectorCenter extends AbstractTag
{

    protected $Id = '72.1';

    protected $Name = 'SubSelectorCenter';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Sub Selector Center';

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
            'Id' => 18,
            'Label' => 'Reset Focus Point',
        ),
        18 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        19 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        20 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        21 => array(
            'Id' => 23,
            'Label' => 'Preset Focus Point',
        ),
        22 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        23 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        24 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        25 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        26 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        27 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        28 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        29 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        30 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        31 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        32 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        33 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        34 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        35 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        36 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        37 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        38 => array(
            'Id' => 54,
            'Label' => 'Highlight Active Focus Point',
        ),
        39 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        40 => array(
            'Id' => 1,
            'Label' => 'Preview',
        ),
        41 => array(
            'Id' => 2,
            'Label' => 'FV Lock',
        ),
        42 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        43 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        44 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        45 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        46 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        47 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        48 => array(
            'Id' => 10,
            'Label' => 'Bracketing Burst',
        ),
        49 => array(
            'Id' => 11,
            'Label' => 'Matrix Metering',
        ),
        50 => array(
            'Id' => 12,
            'Label' => 'Center-weighted Metering',
        ),
        51 => array(
            'Id' => 13,
            'Label' => 'Spot Metering',
        ),
        52 => array(
            'Id' => 14,
            'Label' => 'Playback',
        ),
        53 => array(
            'Id' => 15,
            'Label' => 'My Menu Top Item',
        ),
        54 => array(
            'Id' => 16,
            'Label' => '+NEF(RAW)',
        ),
        55 => array(
            'Id' => 17,
            'Label' => 'Virtual Horizon',
        ),
        56 => array(
            'Id' => 18,
            'Label' => 'Reset Focus Point',
        ),
        57 => array(
            'Id' => 19,
            'Label' => 'Grid Display',
        ),
        58 => array(
            'Id' => 20,
            'Label' => 'My Menu',
        ),
        59 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        60 => array(
            'Id' => 23,
            'Label' => 'Preset Focus Point',
        ),
        61 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        62 => array(
            'Id' => 27,
            'Label' => 'Highlight-weighted Metering',
        ),
        63 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        64 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        65 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        66 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 153 Points)',
        ),
        67 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        68 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        69 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        70 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        71 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        72 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 153 Points)',
        ),
        73 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        74 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        75 => array(
            'Id' => 49,
            'Label' => 'Sync Release (Master Only)',
        ),
        76 => array(
            'Id' => 50,
            'Label' => 'Sync Release (Remote Only)',
        ),
        77 => array(
            'Id' => 54,
            'Label' => 'Highlight Active Focus Point',
        ),
        78 => array(
            'Id' => 56,
            'Label' => 'AF-Area Mode (Dynamic Area 9 Points)',
        ),
        79 => array(
            'Id' => 57,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 9 Points)',
        ),
    );

}
