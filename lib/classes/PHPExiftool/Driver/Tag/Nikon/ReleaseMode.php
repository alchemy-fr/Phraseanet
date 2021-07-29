<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Nikon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ReleaseMode extends AbstractTag
{

    protected $Id = 6221;

    protected $Name = 'ReleaseMode';

    protected $FullName = 'Nikon::ShotInfoD4S';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Release Mode';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Single Frame',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Continuous High Speed',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Continuous Low Speed',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Timer',
        ),
        32 => array(
            'Id' => 32,
            'Label' => 'Mirror-Up',
        ),
        64 => array(
            'Id' => 64,
            'Label' => 'Quiet',
        ),
    );

}
