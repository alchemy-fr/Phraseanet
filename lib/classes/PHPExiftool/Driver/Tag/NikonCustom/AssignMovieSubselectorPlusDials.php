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
class AssignMovieSubselectorPlusDials extends AbstractTag
{

    protected $Id = '76.1';

    protected $Name = 'AssignMovieSubselectorPlusDials';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Assign Movie Subselector Plus Dials';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area',
        ),
        2 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        3 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area (DX/1.3x)',
        ),
        4 => array(
            'Id' => 0,
            'Label' => 'None',
        ),
        5 => array(
            'Id' => 1,
            'Label' => 'Choose Image Area',
        ),
    );

}
