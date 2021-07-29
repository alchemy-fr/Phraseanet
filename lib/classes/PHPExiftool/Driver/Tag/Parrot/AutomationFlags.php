<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Parrot;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AutomationFlags extends AbstractTag
{

    protected $Id = 29;

    protected $Name = 'AutomationFlags';

    protected $FullName = 'Parrot::Automation';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'Location';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Automation Flags';

    protected $local_g2 = 'Device';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Follow-me enabled',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Look-at-me enabled',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Angle locked',
        ),
    );

}
