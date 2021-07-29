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
class LiveViewButtonOptions extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'LiveViewButtonOptions';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Live View Button Options';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Enable',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Enable (standby time active)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Disable',
        ),
        3 => array(
            'Id' => 0,
            'Label' => 'Enable',
        ),
        4 => array(
            'Id' => 1,
            'Label' => 'Enable (Standby Timer Active)',
        ),
        5 => array(
            'Id' => 2,
            'Label' => 'Disable',
        ),
        6 => array(
            'Id' => 0,
            'Label' => 'Enable',
        ),
        7 => array(
            'Id' => 1,
            'Label' => 'Enable (Standby Timer Active)',
        ),
        8 => array(
            'Id' => 2,
            'Label' => 'Disable',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'Enable',
        ),
        10 => array(
            'Id' => 2,
            'Label' => 'Disable',
        ),
        11 => array(
            'Id' => 0,
            'Label' => 'Enable',
        ),
        12 => array(
            'Id' => 1,
            'Label' => 'Enable (Standby Timer Active)',
        ),
        13 => array(
            'Id' => 2,
            'Label' => 'Disable',
        ),
    );

}
