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
class AssignMovieRecordButtonPlusDials extends AbstractTag
{

    protected $Id = '45.1';

    protected $Name = 'AssignMovieRecordButtonPlusDials';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign Movie Record Button Plus Dials';

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
            'Id' => 7,
            'Label' => 'Photo Shooting Menu Bank',
        ),
        4 => array(
            'Id' => 11,
            'Label' => 'Exposure Mode',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area (DX/1.3x)',
        ),
        7 => array(
            'Id' => 2,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        8 => array(
            'Id' => 7,
            'Label' => 'Photo Shooting Menu Bank',
        ),
        9 => array(
            'Id' => 11,
            'Label' => 'Exposure Mode',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Shutter Speed & Aperture Lock',
        ),
        13 => array(
            'Id' => 7,
            'Label' => 'Photo Shooting Menu Bank',
        ),
        14 => array(
            'Id' => 11,
            'Label' => 'Exposure Mode',
        ),
    );

}
