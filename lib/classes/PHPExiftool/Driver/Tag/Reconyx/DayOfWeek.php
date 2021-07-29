<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Reconyx;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class DayOfWeek extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'DayOfWeek';

    protected $FullName = 'mixed';

    protected $GroupName = 'Reconyx';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Reconyx';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Day Of Week';

    protected $local_g2 = 'Time';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Sunday',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Monday',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Tuesday',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Wednesday',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Thursday',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Friday',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Saturday',
        ),
    );

}
