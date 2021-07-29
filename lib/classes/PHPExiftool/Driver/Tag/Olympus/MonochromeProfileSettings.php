<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Olympus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MonochromeProfileSettings extends AbstractTag
{

    protected $Id = 1335;

    protected $Name = 'MonochromeProfileSettings';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = 'int16s';

    protected $Writable = true;

    protected $Description = 'Monochrome Profile Settings';

    protected $flag_Permanent = true;

    protected $MaxLength = 6;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'No Filter',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Yellow Filter',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Orange Filter',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Red Filter',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Magenta Filter',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Blue Filter',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Cyan Filter',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Green Filter',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Yellow-green Filter',
        ),
    );

}
