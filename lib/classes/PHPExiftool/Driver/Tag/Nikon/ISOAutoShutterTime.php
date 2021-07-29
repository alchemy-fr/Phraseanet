<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ISOAutoShutterTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ISOAutoShutterTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'ISO Auto Shutter Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '1/4000 s',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '1/3200 s',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '1/2500 s',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1/2000 s',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '1/1600 s',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '1/1250 s',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '1/1000 s',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '1/800 s',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '1/640 s',
        ),
        9 => array(
            'Id' => 9,
            'Label' => '1/500 s',
        ),
        10 => array(
            'Id' => 10,
            'Label' => '1/400 s',
        ),
        11 => array(
            'Id' => 11,
            'Label' => '1/320 s',
        ),
        12 => array(
            'Id' => 12,
            'Label' => '1/250 s',
        ),
        13 => array(
            'Id' => 13,
            'Label' => '1/200 s',
        ),
        14 => array(
            'Id' => 14,
            'Label' => '1/160 s',
        ),
        15 => array(
            'Id' => 15,
            'Label' => '1/125 s',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '1/100 s',
        ),
        17 => array(
            'Id' => 17,
            'Label' => '1/80 s',
        ),
        18 => array(
            'Id' => 18,
            'Label' => '1/60 s',
        ),
        19 => array(
            'Id' => 19,
            'Label' => '1/50 s',
        ),
        20 => array(
            'Id' => 20,
            'Label' => '1/40 s',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '1/30 s',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '1/15 s',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '1/8 s',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '1/4 s',
        ),
        25 => array(
            'Id' => 25,
            'Label' => '1/2 s',
        ),
        26 => array(
            'Id' => 26,
            'Label' => '1 s',
        ),
        27 => array(
            'Id' => 27,
            'Label' => '2 s',
        ),
        28 => array(
            'Id' => 28,
            'Label' => '4 s',
        ),
        29 => array(
            'Id' => 29,
            'Label' => '8 s',
        ),
        30 => array(
            'Id' => 30,
            'Label' => '15 s',
        ),
        31 => array(
            'Id' => 31,
            'Label' => '30 s',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Auto (Slowest)',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Auto (Slower)',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'Auto',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'Auto (Faster)',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'Auto (Fastest)',
        ),
    );

}
