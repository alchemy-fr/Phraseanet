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
class VerticalAFOnButton extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'VerticalAFOnButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Vertical AF On Button';

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
            'Id' => 7,
            'Label' => 'Same as AF On',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Same as AF On',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'AF On',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'AE/AF Lock',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'AE Lock Only',
        ),
        11 => array(
            'Id' => 4,
            'Label' => 'AE Lock (reset on release)',
        ),
        12 => array(
            'Id' => 5,
            'Label' => 'AE Lock (hold)',
        ),
        13 => array(
            'Id' => 6,
            'Label' => 'AF Lock Only',
        ),
        14 => array(
            'Id' => 7,
            'Label' => 'None',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'AE/AF Lock',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'AE Lock Only',
        ),
        18 => array(
            'Id' => 5,
            'Label' => 'AE Lock (reset on release)',
        ),
        19 => array(
            'Id' => 6,
            'Label' => 'AE Lock (hold)',
        ),
        20 => array(
            'Id' => 7,
            'Label' => 'AF Lock Only',
        ),
        21 => array(
            'Id' => 8,
            'Label' => 'AF-On',
        ),
        22 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        23 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        24 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        25 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 152 Points)',
        ),
        26 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        27 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        28 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        29 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        30 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        31 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 152 Points)',
        ),
        32 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        33 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        34 => array(
            'Id' => 100,
            'Label' => 'Same as AF-On',
        ),
    );

}
