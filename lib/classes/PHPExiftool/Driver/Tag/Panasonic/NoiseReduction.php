<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Panasonic;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class NoiseReduction extends AbstractTag
{

    protected $Id = 45;

    protected $Name = 'NoiseReduction';

    protected $FullName = 'Panasonic::Main';

    protected $GroupName = 'Panasonic';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Panasonic';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Standard',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Low (-1)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'High (+1)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Lowest (-2)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Highest (+2)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '+5',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '+6',
        ),
        65531 => array(
            'Id' => 65531,
            'Label' => '-5',
        ),
        65532 => array(
            'Id' => 65532,
            'Label' => '-4',
        ),
        65533 => array(
            'Id' => 65533,
            'Label' => '-3',
        ),
        65534 => array(
            'Id' => 65534,
            'Label' => '-2',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => '-1',
        ),
    );

}
