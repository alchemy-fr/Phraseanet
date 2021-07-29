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
class GPSDateTime extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'GPSDateTime';

    protected $FullName = 'mixed';

    protected $GroupName = 'GoPro';

    protected $g0 = 'GoPro';

    protected $g1 = 'GoPro';

    protected $g2 = 'mixed';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Date Time';

    protected $local_g2 = 'Time';

}
