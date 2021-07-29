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
class WBBracketingSteps extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WBBracketingSteps';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'WB Bracketing Steps';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'WB Bracketing Disabled',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'b3F 1',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'A3F 1',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'b2F 1',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'A2F 1',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '3F 1',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '5F 1',
        ),
        7 => array(
            'Id' => 7,
            'Label' => '7F 1',
        ),
        8 => array(
            'Id' => 8,
            'Label' => '9F 1',
        ),
        16 => array(
            'Id' => 16,
            'Label' => '0F 2',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'b3F 2',
        ),
        18 => array(
            'Id' => 18,
            'Label' => 'A3F 2',
        ),
        19 => array(
            'Id' => 19,
            'Label' => 'b2F 2',
        ),
        20 => array(
            'Id' => 20,
            'Label' => 'A2F 2',
        ),
        21 => array(
            'Id' => 21,
            'Label' => '3F 2',
        ),
        22 => array(
            'Id' => 22,
            'Label' => '5F 2',
        ),
        23 => array(
            'Id' => 23,
            'Label' => '7F 2',
        ),
        24 => array(
            'Id' => 24,
            'Label' => '9F 2',
        ),
        32 => array(
            'Id' => 32,
            'Label' => '0F 3',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'b3F 3',
        ),
        34 => array(
            'Id' => 34,
            'Label' => 'A3F 3',
        ),
        35 => array(
            'Id' => 35,
            'Label' => 'b2F 3',
        ),
        36 => array(
            'Id' => 36,
            'Label' => 'A2F 3',
        ),
        37 => array(
            'Id' => 37,
            'Label' => '3F 3',
        ),
        38 => array(
            'Id' => 38,
            'Label' => '5F 3',
        ),
        39 => array(
            'Id' => 39,
            'Label' => '7F 3',
        ),
        40 => array(
            'Id' => 40,
            'Label' => '9F 3',
        ),
    );

}
