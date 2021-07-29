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
class SubSelector extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'SubSelector';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Sub Selector';

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
            'Label' => 'My Menu',
        ),
        18 => array(
            'Id' => 19,
            'Label' => 'Reset',
        ),
        19 => array(
            'Id' => 20,
            'Label' => 'Grid Display',
        ),
        20 => array(
            'Id' => 21,
            'Label' => 'Disable Synchronized Release',
        ),
        21 => array(
            'Id' => 22,
            'Label' => 'Remote Release Only',
        ),
        22 => array(
            'Id' => 23,
            'Label' => 'Preview',
        ),
        23 => array(
            'Id' => 26,
            'Label' => 'Flash Disable/Enable',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'Focus Point Selection',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'Same as MultiSelector',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'Focus Point Selection',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'Same as MultiSelector',
        ),
        28 => array(
            'Id' => 0,
            'Label' => 'Focus Point Selection',
        ),
        29 => array(
            'Id' => 1,
            'Label' => 'Same as MultiSelector',
        ),
    );

}
