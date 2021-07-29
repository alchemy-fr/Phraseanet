<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\QuickTime;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class RollingShutterSkewTime extends AbstractTag
{

    protected $Id = 8;

    protected $Name = 'RollingShutterSkewTime';

    protected $FullName = 'QuickTime::camm1';

    protected $GroupName = 'QuickTime';

    protected $g0 = 'QuickTime';

    protected $g1 = 'QuickTime';

    protected $g2 = 'Camera';

    protected $Type = 'int32s';

    protected $Writable = false;

    protected $Description = 'Rolling Shutter Skew Time';

}
