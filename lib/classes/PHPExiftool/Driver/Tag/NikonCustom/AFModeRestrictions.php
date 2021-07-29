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
class AFModeRestrictions extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AFModeRestrictions';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF Mode Restrictions';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Off',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'No Restrictions',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'No Restrictions',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'No Restrictions',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        11 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
        12 => array(
            'Id' => 0,
            'Label' => 'No Restrictions',
        ),
        13 => array(
            'Id' => 1,
            'Label' => 'AF-C',
        ),
        14 => array(
            'Id' => 2,
            'Label' => 'AF-S',
        ),
    );

}
