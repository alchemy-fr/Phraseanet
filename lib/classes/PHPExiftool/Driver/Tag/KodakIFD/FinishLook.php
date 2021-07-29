<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FinishLook extends AbstractTag
{

    protected $Id = 3516;

    protected $Name = 'FinishLook';

    protected $FullName = 'Kodak::IFD';

    protected $GroupName = 'KodakIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KodakIFD';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Finish Look';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Product',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Portrait',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Product Reduced',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Portrait Reduced',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Monochrome Product',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Monochrome Portrait',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'Wedding',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Event',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Product Hi Color',
        ),
        9 => array(
            'Id' => 9,
            'Label' => 'Portrait Hi Color',
        ),
        10 => array(
            'Id' => 10,
            'Label' => 'Product Hi Color Hold',
        ),
        11 => array(
            'Id' => 11,
            'Label' => 'Portrait Hi Color Hold',
        ),
        13 => array(
            'Id' => 13,
            'Label' => 'DCS BW Normal',
        ),
        14 => array(
            'Id' => 14,
            'Label' => 'DCS BW Wratten 8',
        ),
        15 => array(
            'Id' => 15,
            'Label' => 'DCS BW Wratten 25',
        ),
        16 => array(
            'Id' => 16,
            'Label' => 'DCS Sepia 1',
        ),
        17 => array(
            'Id' => 17,
            'Label' => 'DCS Sepia 2',
        ),
    );

}
