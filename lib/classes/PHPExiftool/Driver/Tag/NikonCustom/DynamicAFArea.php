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
class DynamicAFArea extends AbstractTag
{

    protected $Id = '1.4';

    protected $Name = 'DynamicAFArea';

    protected $FullName = 'mixed';

    protected $GroupName = 'NikonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'NikonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = true;

    protected $Description = 'Dynamic AF Area';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => '9 Points',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '21 Points',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '51 Points',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '51 Points (3D-tracking)',
        ),
    );

}
