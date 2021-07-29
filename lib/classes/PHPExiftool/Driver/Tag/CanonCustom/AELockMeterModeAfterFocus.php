<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CanonCustom;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AELockMeterModeAfterFocus extends AbstractTag
{

    protected $Id = 276;

    protected $Name = 'AELockMeterModeAfterFocus';

    protected $FullName = 'CanonCustom::Functions2';

    protected $GroupName = 'CanonCustom';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'CanonCustom';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = true;

    protected $Description = 'AE Lock Meter Mode After Focus';

    protected $flag_Permanent = true;

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Evaluative',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Partial',
        ),
        4 => array(
            'Id' => 4,
            'Label' => 'Spot',
        ),
        8 => array(
            'Id' => 8,
            'Label' => 'Center-weighted',
        ),
    );

}
