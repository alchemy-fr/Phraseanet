<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\IFD0;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CFAPattern extends AbstractTag
{

    protected $Id = 9;

    protected $Name = 'CFAPattern';

    protected $FullName = 'PanasonicRaw::Main';

    protected $GroupName = 'IFD0';

    protected $g0 = 'EXIF';

    protected $g1 = 'IFD0';

    protected $g2 = 'Image';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'CFA Pattern';

    protected $flag_Unsafe = true;

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'n/a',
        ),
        1 => array(
            'Id' => 1,
            'Label' => '[Red,Green][Green,Blue]',
        ),
        2 => array(
            'Id' => 2,
            'Label' => '[Green,Red][Blue,Green]',
        ),
        3 => array(
            'Id' => 3,
            'Label' => '[Green,Blue][Red,Green]',
        ),
        4 => array(
            'Id' => 4,
            'Label' => '[Blue,Green][Green,Red]',
        ),
    );

}
