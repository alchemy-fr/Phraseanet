<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\CameraIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class WBCFA2LevelDaylight extends AbstractTag
{

    protected $Id = 12802;

    protected $Name = 'WB_CFA2_LevelDaylight';

    protected $FullName = 'PanasonicRaw::CameraIFD';

    protected $GroupName = 'CameraIFD';

    protected $g0 = 'PanasonicRaw';

    protected $g1 = 'CameraIFD';

    protected $g2 = 'Camera';

    protected $Type = 'int16u';

    protected $Writable = true;

    protected $Description = 'WB CFA2 Level Daylight';

}
