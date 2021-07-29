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
class CommandDialsReverseRotation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CommandDialsReverseRotation';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Command Dials Reverse Rotation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Shutter Speed & Aperture',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Exposure Compensation',
        ),
        5 => array(
            'Id' => 3,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Shutter Speed & Aperture',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Exposure Compensation',
        ),
        9 => array(
            'Id' => 3,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Shutter Speed & Aperture',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Exposure Compensation',
        ),
        13 => array(
            'Id' => 3,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        21 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'Shutter Speed & Aperture',
        ),
        24 => array(
            'Id' => 2,
            'Label' => 'Exposure Compensation',
        ),
        25 => array(
            'Id' => 3,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'Shutter Speed & Aperture',
        ),
        28 => array(
            'Id' => 2,
            'Label' => 'Exposure Compensation',
        ),
        29 => array(
            'Id' => 3,
            'Label' => 'Exposure Compensation, Shutter Speed & Aperture',
        ),
        30 => array(
            'Id' => 0,
            'Label' => 'No',
        ),
        31 => array(
            'Id' => 1,
            'Label' => 'Yes',
        ),
    );

}
