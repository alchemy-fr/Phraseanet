<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Canon;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AFPointDisplayDuringFocus extends AbstractTag
{

    protected $Id = 16;

    protected $Name = 'AFPointDisplayDuringFocus';

    protected $FullName = 'Canon::AFConfig';

    protected $GroupName = 'Canon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Canon';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'AF Point Display During Focus';

    protected $flag_Permanent = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Selected (constant)',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'All (constant)',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Selected (pre-AF, focused)',
        ),
        3 => array(
            'Id' => 3,
            'Label' => 'Selected (focused)',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Disabled',
        ),
    );

}
