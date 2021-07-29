<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Sony;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class PictureProfile extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PictureProfile';

    protected $FullName = 'mixed';

    protected $GroupName = 'Sony';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Sony';

    protected $g2 = 'Image';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Picture Profile';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Gamma Still - Standard/Neutral (PP2)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Gamma Still - Portrait',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Gamma Still - Night View/Portrait',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Gamma Still - B&W/Sepia',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Gamma Still - Clear',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Gamma Still - Deep',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Gamma Still - Light',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Gamma Still - Vivid',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Gamma Still - Real',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Gamma Movie (PP1)',
        ),
        22 => array(
            'Id' => 22,
            'Label' => 'Gamma ITU709 (PP3 or PP4)',
        ),
        24 => array(
            'Id' => 24,
            'Label' => 'Gamma Cine1 (PP5)',
        ),
        25 => array(
            'Id' => 25,
            'Label' => 'Gamma Cine2 (PP6)',
        ),
        26 => array(
            'Id' => 26,
            'Label' => 'Gamma Cine3',
        ),
        27 => array(
            'Id' => 27,
            'Label' => 'Gamma Cine4',
        ),
        28 => array(
            'Id' => 28,
            'Label' => 'Gamma S-Log2 (PP7)',
        ),
        29 => array(
            'Id' => 29,
            'Label' => 'Gamma ITU709 (800%)',
        ),
        31 => array(
            'Id' => 31,
            'Label' => 'Gamma S-Log3 (PP8 or PP9)',
        ),
        33 => array(
            'Id' => 33,
            'Label' => 'Gamma HLG2 (PP10)',
        ),
    );

}
