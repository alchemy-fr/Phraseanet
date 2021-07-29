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
class LensID extends AbstractTag
{

    protected $Id = 48;

    protected $Name = 'LensID';

    protected $FullName = 'Nikon::LensData0800';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Lens ID';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Nikkor Z 24-70mm f/4 S',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Nikkor Z 14-30mm f/4 S',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Nikkor Z 35mm f/1.8 S',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Nikkor Z 58mm f/0.95 S Noct',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Nikkor Z 50mm f/1.8 S',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Nikkor Z DX 16-50mm f/3.5-6.3 VR',
        ),
        12 => array(
            'Id' => 12,
            'Label' => 'Nikkor Z DX 50-250mm f/4.5-6.3 VR',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'Nikkor Z 24-70mm f/2.8 S',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'Nikkor Z 85mm f/1.8 S',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'Nikkor Z 24mm f/1.8 S',
        ),
    );

}
