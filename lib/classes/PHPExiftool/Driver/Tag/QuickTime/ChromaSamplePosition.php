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
class ChromaSamplePosition extends AbstractTag
{

    protected $Id = '2.4';

    protected $Name = 'ChromaSamplePosition';

    protected $FullName = 'QuickTime::AV1Config';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Video';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Chroma Sample Position';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Unknown',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Vertical',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Colocated',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '(reserved)',
        ),
    );

}
