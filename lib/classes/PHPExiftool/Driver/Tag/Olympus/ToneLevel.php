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
class ToneLevel extends AbstractTag
{

    protected $Id = 1326;

    protected $Name = 'ToneLevel';

    protected $FullName = 'Olympus::CameraSettings';

    protected $GroupName = 'Olympus';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Olympus';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Tone Level';

    protected $flag_Permanent = true;

    protected $Values = array(
        '-31999' => array(
            'Id' => '-31999',
            'Label' => 'Highlights',
        ),
        '-31998' => array(
            'Id' => '-31998',
            'Label' => 'Shadows',
        ),
        '-31997' => array(
            'Id' => '-31997',
            'Label' => 'Midtones',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 0,
        ),
    );

}
