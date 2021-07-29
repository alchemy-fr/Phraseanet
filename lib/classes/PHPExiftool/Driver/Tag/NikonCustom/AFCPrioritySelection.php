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
class AFCPrioritySelection extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AF-CPrioritySelection';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF-C Priority Selection';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'Focus + Release',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        10 => array(
            'Id' => 3,
            'Label' => 'Focus + Release',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        14 => array(
            'Id' => 3,
            'Label' => 'Focus + Release',
        ),
        15 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        16 => array(
            'Id' => 1,
            'Label' => 'Focus',
        ),
        17 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        18 => array(
            'Id' => 1,
            'Label' => 'Focus',
        ),
        19 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        20 => array(
            'Id' => 1,
            'Label' => 'Focus',
        ),
        21 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        22 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        23 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        24 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        25 => array(
            'Id' => 1,
            'Label' => 'Focus',
        ),
        26 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        27 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        28 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        29 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        30 => array(
            'Id' => 1,
            'Label' => 'Release + Focus',
        ),
        31 => array(
            'Id' => 2,
            'Label' => 'Focus',
        ),
        32 => array(
            'Id' => 3,
            'Label' => 'Focus + Release',
        ),
    );

}
