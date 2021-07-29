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
class AFPointIllumination extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointIllumination';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point Illumination';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'On in Continuous Shooting and Manual Focusing',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On During Manual Focusing',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'On in Continuous Shooting Modes',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'On in Continuous Shooting Modes',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'On During Manual Focusing',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'On in Continuous Shooting and Manual Focusing',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'On During Manual Focusing',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'On During Manual Focusing',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        17 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Off',
        ),
        20 => array(
            'Id' => 2,
            'Label' => 'On',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        22 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        23 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'On During Manual Focusing',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'On During Manual Focusing',
        ),
        28 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        29 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        30 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
    );

    protected $Index = 'mixed';

}
