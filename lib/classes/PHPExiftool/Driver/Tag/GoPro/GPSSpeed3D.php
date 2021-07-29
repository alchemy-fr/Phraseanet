<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GoPro;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GPSSpeed3D extends AbstractTag
{

    protected $Id = 4;

    protected $Name = 'GPSSpeed3D';

    protected $FullName = 'GoPro::GPS5';

    protected $GroupName = 'GoPro';

    protected $g0 = 'GoPro';

    protected $g1 = 'GoPro';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Speed 3D';

}
