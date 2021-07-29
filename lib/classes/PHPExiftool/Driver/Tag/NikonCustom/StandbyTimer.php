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
class StandbyTimer extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'StandbyTimer';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Standby Timer';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '6 s',
        ),
        2 => array(
            'Id' => 3,
            'Label' => '10 s',
        ),
        3 => array(
            'Id' => 5,
            'Label' => '30 s',
        ),
        4 => array(
            'Id' => 6,
            'Label' => '1 min',
        ),
        5 => array(
            'Id' => 7,
            'Label' => '5 min',
        ),
        6 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        7 => array(
            'Id' => 9,
            'Label' => '30 min',
        ),
        8 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        9 => array(
            'Id' => 1,
            'Label' => '6 s',
        ),
        10 => array(
            'Id' => 3,
            'Label' => '10 s',
        ),
        11 => array(
            'Id' => 5,
            'Label' => '30 s',
        ),
        12 => array(
            'Id' => 6,
            'Label' => '1 min',
        ),
        13 => array(
            'Id' => 7,
            'Label' => '5 min',
        ),
        14 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        15 => array(
            'Id' => 9,
            'Label' => '30 min',
        ),
        16 => array(
            'Id' => 10,
            'Label' => 'No Limit',
        ),
        17 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        18 => array(
            'Id' => 1,
            'Label' => '6 s',
        ),
        19 => array(
            'Id' => 3,
            'Label' => '10 s',
        ),
        20 => array(
            'Id' => 5,
            'Label' => '30 s',
        ),
        21 => array(
            'Id' => 6,
            'Label' => '1 min',
        ),
        22 => array(
            'Id' => 7,
            'Label' => '5 min',
        ),
        23 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        24 => array(
            'Id' => 9,
            'Label' => '30 min',
        ),
        25 => array(
            'Id' => 10,
            'Label' => 'No Limit',
        ),
        26 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        27 => array(
            'Id' => 1,
            'Label' => '8 s',
        ),
        28 => array(
            'Id' => 2,
            'Label' => '20 s',
        ),
        29 => array(
            'Id' => 3,
            'Label' => '1 min',
        ),
        30 => array(
            'Id' => 4,
            'Label' => '30 min',
        ),
        31 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        32 => array(
            'Id' => 1,
            'Label' => '6 s',
        ),
        33 => array(
            'Id' => 2,
            'Label' => '10 s',
        ),
        34 => array(
            'Id' => 3,
            'Label' => '30 s',
        ),
        35 => array(
            'Id' => 4,
            'Label' => '1 min',
        ),
        36 => array(
            'Id' => 5,
            'Label' => '5 min',
        ),
        37 => array(
            'Id' => 6,
            'Label' => '10 min',
        ),
        38 => array(
            'Id' => 7,
            'Label' => '30 min',
        ),
        39 => array(
            'Id' => 8,
            'Label' => 'No Limit',
        ),
        40 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        41 => array(
            'Id' => 1,
            'Label' => '6 s',
        ),
        42 => array(
            'Id' => 3,
            'Label' => '10 s',
        ),
        43 => array(
            'Id' => 5,
            'Label' => '30 s',
        ),
        44 => array(
            'Id' => 6,
            'Label' => '1 min',
        ),
        45 => array(
            'Id' => 7,
            'Label' => '5 min',
        ),
        46 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        47 => array(
            'Id' => 9,
            'Label' => '30 min',
        ),
        48 => array(
            'Id' => 10,
            'Label' => 'No Limit',
        ),
        49 => array(
            'Id' => 0,
            'Label' => '4 s',
        ),
        50 => array(
            'Id' => 1,
            'Label' => '6 s',
        ),
        51 => array(
            'Id' => 3,
            'Label' => '10 s',
        ),
        52 => array(
            'Id' => 5,
            'Label' => '30 s',
        ),
        53 => array(
            'Id' => 6,
            'Label' => '1 min',
        ),
        54 => array(
            'Id' => 7,
            'Label' => '5 min',
        ),
        55 => array(
            'Id' => 8,
            'Label' => '10 min',
        ),
        56 => array(
            'Id' => 9,
            'Label' => '30 min',
        ),
        57 => array(
            'Id' => 10,
            'Label' => 'No Limit',
        ),
    );

}
