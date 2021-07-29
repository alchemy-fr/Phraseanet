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
class ExposureDelayMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'ExposureDelayMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Exposure Delay Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        3 => array(
            'Id' => 1,
            'Label' => '1 s',
        ),
        4 => array(
            'Id' => 2,
            'Label' => '2 s',
        ),
        5 => array(
            'Id' => 3,
            'Label' => '3 s',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        7 => array(
            'Id' => 1,
            'Label' => '1 s',
        ),
        8 => array(
            'Id' => 2,
            'Label' => '2 s',
        ),
        9 => array(
            'Id' => 3,
            'Label' => '3 s',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        11 => array(
            'Id' => 1,
            'Label' => '1 s',
        ),
        12 => array(
            'Id' => 2,
            'Label' => '2 s',
        ),
        13 => array(
            'Id' => 3,
            'Label' => '3 s',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        21 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        22 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        23 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        27 => array(
            'Id' => 1,
            'Label' => '1 s',
        ),
        28 => array(
            'Id' => 2,
            'Label' => '2 s',
        ),
        29 => array(
            'Id' => 3,
            'Label' => '3 s',
        ),
        30 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        31 => array(
            'Id' => 1,
            'Label' => '0.2 s',
        ),
        32 => array(
            'Id' => 2,
            'Label' => '0.5 s',
        ),
        33 => array(
            'Id' => 3,
            'Label' => '1 s',
        ),
        34 => array(
            'Id' => 4,
            'Label' => '2 s',
        ),
        35 => array(
            'Id' => 5,
            'Label' => '3 s',
        ),
        36 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        37 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
    );

}
