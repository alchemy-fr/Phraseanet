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
class TriggerMode extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'TriggerMode';

    protected $FullName = 'mixed';

    protected $GroupName = 'Reconyx';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Reconyx';

    protected $g2 = 'Camera';

    protected $Type = 'mixed';

    protected $Writable = true;

    protected $Description = 'Trigger Mode';

    protected $flag_Permanent = true;

    protected $MaxLength = 'mixed';

    protected $Values = array(
        'C' => array(
            'Id' => 'C',
            'Label' => 'CodeLoc Not Entered',
        ),
        'E' => array(
            'Id' => 'E',
            'Label' => 'External Sensor',
        ),
        'M' => array(
            'Id' => 'M',
            'Label' => 'Motion Detection',
        ),
        'T' => array(
            'Id' => 'T',
            'Label' => 'Time Lapse',
        ),
        'P' => array(
            'Id' => 'P',
            'Label' => 'Point and Shoot',
        ),
    );

}
