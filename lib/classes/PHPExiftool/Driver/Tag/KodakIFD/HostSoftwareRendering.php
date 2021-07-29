<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class HostSoftwareRendering extends AbstractTag
{

    protected $Id = 3303;

    protected $Name = 'HostSoftwareRendering';

    protected $FullName = 'Kodak::IFD';

    protected $GroupName = 'KodakIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KodakIFD';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Host Software Rendering';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Normal (sRGB)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Linear (camera RGB)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Pro Photo RGB',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Unknown',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Other Profile',
        ),
    );

}
