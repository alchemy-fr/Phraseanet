<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\GPS;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class GPSDestLatitude extends AbstractTag
{

    protected $Id = 20;

    protected $Name = 'GPSDestLatitude';

    protected $FullName = 'GPS::Main';

    protected $GroupName = 'GPS';

    protected $g0 = 'EXIF';

    protected $g1 = 'GPS';

    protected $g2 = 'Location';

    protected $Type = 'rational64u';

    protected $Writable = true;

    protected $Description = 'GPS Dest Latitude';

    protected $MaxLength = 3;

}
