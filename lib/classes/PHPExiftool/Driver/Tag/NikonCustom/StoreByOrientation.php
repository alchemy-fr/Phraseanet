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
class StoreByOrientation extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'StoreByOrientation';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Store By Orientation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Focus Point',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Focus Point and AF-area mode',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Focus Point',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Focus Point and AF-area Mode',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Focus Point',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Focus Point and AF-area Mode',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Focus Point',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'Focus Point and AF-area mode',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'Focus Point',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'Focus Point and AF-area Mode',
        ),
    );

}
