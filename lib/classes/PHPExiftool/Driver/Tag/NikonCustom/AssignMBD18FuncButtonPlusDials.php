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
class AssignMBD18FuncButtonPlusDials extends AbstractTag
{

    protected $Id = '68.1';

    protected $Name = 'AssignMB-D18FuncButtonPlusDials';

    protected $FullName = 'NikonCustom::SettingsD850';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign MB-D18 Func Button Plus Dials';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'One Step Speed / Aperture',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Choose Non-CPU Lens Number',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Active D-Lighting',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Photo Shooting Menu Bank',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Exposure Delay Mode',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'ISO Sensitivity',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Exposure Mode',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Exposure Compensation',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Metering Mode',
        ),
    );

}
