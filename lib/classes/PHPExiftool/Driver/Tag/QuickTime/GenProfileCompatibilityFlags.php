<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GenProfileCompatibilityFlags extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'GenProfileCompatibilityFlags';

    protected $FullName = 'QuickTime::HEVCConfig';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'Gen Profile Compatibility Flags';

    protected $Values = array(
        268435456 => array(
            'Id' => 268435456,
            'Label' => 'Main Still Picture',
        ),
        536870912 => array(
            'Id' => 536870912,
            'Label' => 'Main 10',
        ),
        1073741824 => array(
            'Id' => 1073741824,
            'Label' => 'Main',
        ),
        '2147483648' => array(
            'Id' => '2147483648',
            'Label' => 'No Profile',
        ),
    );

}
