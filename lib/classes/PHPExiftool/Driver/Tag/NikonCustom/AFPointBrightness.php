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
class AFPointBrightness extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFPointBrightness';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Point Brightness';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Low',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'High',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Extra High',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        6 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        7 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        8 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        9 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        12 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
        13 => array(
            'Id' => 0,
            'Label' => 'Auto',
        ),
        14 => array(
            'Id' => 1,
            'Label' => 'On',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Off',
        ),
    );

}
