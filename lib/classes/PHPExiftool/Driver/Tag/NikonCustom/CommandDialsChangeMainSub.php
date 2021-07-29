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
class CommandDialsChangeMainSub extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'CommandDialsChangeMainSub';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Command Dials Change Main Sub';

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
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        4 => array(
            'Id' => 2,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        5 => array(
            'Id' => 4,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        6 => array(
            'Id' => 5,
            'Label' => 'Autofocus On, Exposure On',
        ),
        7 => array(
            'Id' => 6,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        11 => array(
            'Id' => 4,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        12 => array(
            'Id' => 5,
            'Label' => 'Autofocus On, Exposure On',
        ),
        13 => array(
            'Id' => 6,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        15 => array(
            'Id' => 1,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        16 => array(
            'Id' => 2,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        18 => array(
            'Id' => 5,
            'Label' => 'Autofocus On, Exposure On',
        ),
        19 => array(
            'Id' => 6,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
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
            'Id' => 2,
            'Label' => 'On (A mode only)',
        ),
        25 => array(
            'Id' => 0,
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        26 => array(
            'Id' => 1,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        27 => array(
            'Id' => 2,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        28 => array(
            'Id' => 4,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        29 => array(
            'Id' => 5,
            'Label' => 'Autofocus On, Exposure On',
        ),
        30 => array(
            'Id' => 6,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
        ),
        31 => array(
            'Id' => 0,
            'Label' => 'Autofocus Off, Exposure Off',
        ),
        32 => array(
            'Id' => 1,
            'Label' => 'Autofocus Off, Exposure On',
        ),
        33 => array(
            'Id' => 2,
            'Label' => 'Autofocus Off, Exposure On (Mode A)',
        ),
        34 => array(
            'Id' => 4,
            'Label' => 'Autofocus On, Exposure Off',
        ),
        35 => array(
            'Id' => 5,
            'Label' => 'Autofocus On, Exposure On',
        ),
        36 => array(
            'Id' => 6,
            'Label' => 'Autofocus On, Exposure On (Mode A)',
        ),
    );

}
