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
class AFOnButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'mixed';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'mixed';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF On',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AE/AF Lock',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AE Lock Only',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE Lock (reset on release)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'AE Lock (hold)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'AF Lock Only',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'AF On',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'AE/AF Lock',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'AE Lock Only',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'AE Lock (reset on release)',
        ),
        10 => array(
            'Id' => 4,
            'Label' => 'AE Lock (hold)',
        ),
        11 => array(
            'Id' => 5,
            'Label' => 'AF Lock Only',
        ),
        12 => array(
            'Id' => 6,
            'Label' => 'None',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        14 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        15 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        16 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        17 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        18 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        19 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        20 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        21 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        22 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        23 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        24 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        25 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        26 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        27 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        28 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        29 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        30 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        31 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        32 => array(
            'Id' => 0,
            'Label' => 'None',
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
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        36 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        37 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        38 => array(
            'Id' => 8,
            'Label' => 'AF-On',
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
            'Id' => 0,
            'Label' => 'None',
        ),
        52 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        53 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        54 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        55 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        56 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        57 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        58 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        59 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        60 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        61 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 153 Points)',
        ),
        62 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        63 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        64 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        65 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        66 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        67 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 153 Points)',
        ),
        68 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        69 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        70 => array(
            'Id' => 56,
            'Label' => 'AF-Area Mode (Dynamic Area 9 Points)',
        ),
        71 => array(
            'Id' => 57,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 9 Points)',
        ),
    );

}
