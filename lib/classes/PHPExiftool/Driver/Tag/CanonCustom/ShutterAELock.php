<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ShutterAELock extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'Shutter-AELock';

    protected $FullName = 'mixed';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Shutter-AE Lock';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'AF/AE lock',
        ),
        '1 0' => array(
            'Id' => '1 0',
            'Label' => 'AE lock/AF',
        ),
        '2 0' => array(
            'Id' => '2 0',
            'Label' => 'AF/AF lock, No AE lock',
        ),
        '3 0' => array(
            'Id' => '3 0',
            'Label' => 'AE/AF, No AE lock',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        7 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        15 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        16 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        17 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        18 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        19 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        20 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        21 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        22 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock, No AE lock',
        ),
        23 => array(
            'Id' => 3,
            'Label' => 'AE/AF, No AE lock',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'AF/AE lock',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'AE lock/AF',
        ),
        26 => array(
            'Id' => 2,
            'Label' => 'AF/AF lock',
        ),
        27 => array(
            'Id' => 3,
            'Label' => 'AE+release/AE+AF',
        ),
    );

    protected $Index = 'mixed';

}
