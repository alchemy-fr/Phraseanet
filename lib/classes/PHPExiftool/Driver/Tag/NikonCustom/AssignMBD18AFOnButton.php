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
class AssignMBD18AFOnButton extends AbstractTag
{

    protected $Id = '79.1';

    protected $Name = 'AssignMB-D18AF-OnButton';

    protected $FullName = 'NikonCustom::SettingsD850';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign MB-D18 AF-On Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
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
        36 => array(
            'Id' => 36,
            'Label' => 'AF-Area Mode (Single)',
        ),
        37 => array(
            'Id' => 37,
            'Label' => 'AF-Area Mode (Dynamic Area 25 Points)',
        ),
        38 => array(
            'Id' => 38,
            'Label' => 'AF-Area Mode (Dynamic Area 72 Points)',
        ),
        39 => array(
            'Id' => 39,
            'Label' => 'AF-Area Mode (Dynamic Area 153 Points)',
        ),
        40 => array(
            'Id' => 40,
            'Label' => 'AF-Area Mode (Group Area AF)',
        ),
        41 => array(
            'Id' => 41,
            'Label' => 'AF-Area Mode (Auto Area AF)',
        ),
        42 => array(
            'Id' => 42,
            'Label' => 'AF-Area Mode + AF-On (Single)',
        ),
        43 => array(
            'Id' => 43,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 25 Points)',
        ),
        44 => array(
            'Id' => 44,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 72 Points)',
        ),
        45 => array(
            'Id' => 45,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 153 Points)',
        ),
        46 => array(
            'Id' => 46,
            'Label' => 'AF-Area Mode + AF-On (Group Area AF)',
        ),
        47 => array(
            'Id' => 47,
            'Label' => 'AF-Area Mode + AF-On (Auto Area AF)',
        ),
        56 => array(
            'Id' => 56,
            'Label' => 'AF-Area Mode (Dynamic Area 9 Points)',
        ),
        57 => array(
            'Id' => 57,
            'Label' => 'AF-Area Mode + AF-On (Dynamic Area 9 Points)',
        ),
        100 => array(
            'Id' => 100,
            'Label' => 'Same as Camera AF-On Button',
        ),
    );

}
