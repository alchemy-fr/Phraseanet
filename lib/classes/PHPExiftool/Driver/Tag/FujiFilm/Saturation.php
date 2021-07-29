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
class Saturation extends AbstractTag
{

    protected $Id = 4099;

    protected $Name = 'Saturation';

    protected $FullName = 'FujiFilm::Main';

    protected $GroupName = 'FujiFilm';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'FujiFilm';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Saturation';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '0 (normal)',
        ),
        128 => array(
            'Id' => 128,
            'Label' => '+1 (medium high)',
        ),
        192 => array(
            'Id' => 192,
            'Label' => '+3 (very high)',
        ),
        224 => array(
            'Id' => 224,
            'Label' => '+4 (highest)',
        ),
        256 => array(
            'Id' => 256,
            'Label' => '+2 (high)',
        ),
        384 => array(
            'Id' => 384,
            'Label' => '-1 (medium low)',
        ),
        512 => array(
            'Id' => 512,
            'Label' => 'Low',
        ),
        768 => array(
            'Id' => 768,
            'Label' => 'None (B&W)',
        ),
        769 => array(
            'Id' => 769,
            'Label' => 'B&W Red Filter',
        ),
        770 => array(
            'Id' => 770,
            'Label' => 'B&W Yellow Filter',
        ),
        771 => array(
            'Id' => 771,
            'Label' => 'B&W Green Filter',
        ),
        784 => array(
            'Id' => 784,
            'Label' => 'B&W Sepia',
        ),
        1024 => array(
            'Id' => 1024,
            'Label' => '-2 (low)',
        ),
        1216 => array(
            'Id' => 1216,
            'Label' => '-3 (very low)',
        ),
        1248 => array(
            'Id' => 1248,
            'Label' => '-4 (lowest)',
        ),
        1280 => array(
            'Id' => 1280,
            'Label' => 'Acros',
        ),
        1281 => array(
            'Id' => 1281,
            'Label' => 'Acros Red Filter',
        ),
        1282 => array(
            'Id' => 1282,
            'Label' => 'Acros Yellow Filter',
        ),
        1283 => array(
            'Id' => 1283,
            'Label' => 'Acros Green Filter',
        ),
        32768 => array(
            'Id' => 32768,
            'Label' => 'Film Simulation',
        ),
    );

}
