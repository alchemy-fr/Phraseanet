<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\FujiFilm;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Sharpness extends AbstractTag
{

    protected $Id = 4097;

    protected $Name = 'Sharpness';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Sharpness';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '-4 (softest)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '-3 (very soft)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '-2 (soft)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '0 (normal)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '+2 (hard)',
        ),
        5 => array(
            'Id' => 5,
            'Label' => '+3 (very hard)',
        ),
        6 => array(
            'Id' => 6,
            'Label' => '+4 (hardest)',
        ),
        130 => array(
            'Id' => 130,
            'Label' => '-1 (medium soft)',
        ),
        132 => array(
            'Id' => 132,
            'Label' => '+1 (medium hard)',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Film Simulation',
        ),
        65535 => array(
            'Id' => 65535,
            'Label' => 'n/a',
        ),
    );

}
