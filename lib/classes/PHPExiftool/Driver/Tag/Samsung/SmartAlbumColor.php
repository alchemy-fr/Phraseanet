<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Samsung;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class SmartAlbumColor extends AbstractTag
{

    protected $Id = 32;

    protected $Name = 'SmartAlbumColor';

    protected $FullName = 'Samsung::Type2';

    protected $GroupName = 'Samsung';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Samsung';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'Smart Album Color';

    protected $flag_Permanent = true;

    protected $MaxLength = 2;

    protected $Values = array(
        '0 0' => array(
            'Id' => '0 0',
            'Label' => 'n/a',
        ),
        0 => array(
            'Id' => 0,
            'Label' => 'Red',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Yellow',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Green',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Blue',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Magenta',
        ),
        5 => array(
            'Id' => 5,
            'Label' => 'Black',
        ),
        6 => array(
            'Id' => 6,
            'Label' => 'White',
        ),
        7 => array(
            'Id' => 7,
            'Label' => 'Various',
        ),
    );

    protected $Index = 'mixed';

}
