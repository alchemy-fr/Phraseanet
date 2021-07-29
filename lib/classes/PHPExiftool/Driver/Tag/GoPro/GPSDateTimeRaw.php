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
class GPSDateTimeRaw extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'GPSDateTimeRaw';

    protected $FullName = 'GoPro::GPRI';

    protected $GroupName = 'GoPro';

    protected $g0 = 'GoPro';

    protected $g1 = 'GoPro';

    protected $g2 = 'Location';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Date Time Raw';

    protected $local_g2 = 'Time';

}
