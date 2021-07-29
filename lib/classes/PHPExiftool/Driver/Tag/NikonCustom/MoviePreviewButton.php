<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\NikonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class MoviePreviewButton extends AbstractTag
{

    protected $Id = '41.2';

    protected $Name = 'MoviePreviewButton';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Movie Preview Button';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 2,
            'Label' => 'Power Aperture (open)',
        ),
        2 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        3 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Power Aperture (open)',
        ),
        6 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        7 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
        8 => array(
            'Id' => 10,
            'Label' => 'Exposure Compensation +',
        ),
        9 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        10 => array(
            'Id' => 1,
            'Label' => 'Power Aperture (open)',
        ),
        11 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        12 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
        13 => array(
            'Id' => 10,
            'Label' => 'Exposure Compensation +',
        ),
        14 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        15 => array(
            'Id' => 2,
            'Label' => 'Power Aperture (open)',
        ),
        16 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        17 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
        18 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        19 => array(
            'Id' => 1,
            'Label' => 'Power Aperture (open)',
        ),
        20 => array(
            'Id' => 3,
            'Label' => 'Index Marking',
        ),
        21 => array(
            'Id' => 4,
            'Label' => 'View Photo Shooting Info',
        ),
        22 => array(
            'Id' => 10,
            'Label' => 'Exposure Compensation +',
        ),
    );

}
