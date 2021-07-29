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
class AFSPrioritySelection extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'AF-SPrioritySelection';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AF-S Priority Selection';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Focus',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Release',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'Release',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Focus',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'Focus',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Release',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Focus',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Release',
        ),
        8 => array(
            'Id' => 0,
            'Label' => 'Focus',
        ),
        9 => array(
            'Id' => 1,
            'Label' => 'Release',
        ),
        10 => array(
            'Id' => 0,
            'Label' => 'Focus',
        ),
        11 => array(
            'Id' => 1,
            'Label' => 'Release',
        ),
    );

}
