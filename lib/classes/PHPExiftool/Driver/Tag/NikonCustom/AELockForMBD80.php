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
class AELockForMBD80 extends AbstractTag
{

    protected $Id = '3.1';

    protected $Name = 'AELockForMB-D80';

    protected $FullName = 'NikonCustom::SettingsD90';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'AE Lock For MB-D80';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'AE Lock Only',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'AF Lock Only',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'AE Lock (hold)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'AF-On',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'FV Lock',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Focus Point Selection',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'AE/AF Lock',
        ),
    );

}
