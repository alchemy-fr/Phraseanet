<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\VCalendar;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class InstanceType extends AbstractTag
{

    protected $Id = 'X-microsoft-cdo-insttype';

    protected $Name = 'InstanceType';

    protected $FullName = 'VCard::VCalendar';

    protected $GroupName = 'VCalendar';

    protected $g0 = 'VCard';

    protected $g1 = 'VCalendar';

    protected $g2 = 'Document';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Instance Type';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Non-recurring Appointment',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Recurring Appointment',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Single Instance of Recurring Appointment',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Exception to Recurring Appointment',
        ),
    );

}
