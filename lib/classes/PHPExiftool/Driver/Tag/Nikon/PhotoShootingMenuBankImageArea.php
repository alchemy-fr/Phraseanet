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
class PhotoShootingMenuBankImageArea extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'PhotoShootingMenuBankImageArea';

    protected $FullName = 'mixed';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = true;

    protected $Description = 'Photo Shooting Menu Bank Image Area';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'FX (36x24)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'DX (24x16)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '5:4 (30x24)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '1.2x (30x20)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '1.3x (18x12)',
        ),
        5 => array(
            'Id' => 0,
            'Label' => 'FX (36x24)',
        ),
        6 => array(
            'Id' => 1,
            'Label' => 'DX (24x16)',
        ),
        7 => array(
            'Id' => 2,
            'Label' => '5:4 (30x24)',
        ),
        8 => array(
            'Id' => 3,
            'Label' => '1.2x (30x20)',
        ),
        9 => array(
            'Id' => 4,
            'Label' => '1:1 (24x24)',
        ),
    );

}
