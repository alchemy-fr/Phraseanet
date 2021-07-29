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
class NoiseReduction extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'NoiseReduction';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Noise Reduction';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 64,
            'Label' => 'Low',
        ),
        1 => array(
            'Id' => 128,
            'Label' => 'Normal',
        ),
        2 => array(
            'Id' => 256,
            'Label' => 'n/a',
        ),
        3 => array(
            'Id' => 0,
            'Label' => '0 (normal)',
        ),
        4 => array(
            'Id' => 256,
            'Label' => '+2 (strong)',
        ),
        5 => array(
            'Id' => 384,
            'Label' => '+1 (medium strong)',
        ),
        6 => array(
            'Id' => 448,
            'Label' => '+3 (very strong)',
        ),
        7 => array(
            'Id' => 480,
            'Label' => '+4 (strongest)',
        ),
        8 => array(
            'Id' => 512,
            'Label' => '-2 (weak)',
        ),
        9 => array(
            'Id' => 640,
            'Label' => '-1 (medium weak)',
        ),
        10 => array(
            'Id' => 704,
            'Label' => '-3 (very weak)',
        ),
        11 => array(
            'Id' => 736,
            'Label' => '-4 (weakest)',
        ),
    );

}
