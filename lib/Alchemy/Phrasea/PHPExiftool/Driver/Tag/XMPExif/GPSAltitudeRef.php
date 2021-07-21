<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPExif;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class GPSAltitudeRef extends AbstractTag
{

    protected $Id = 'GPSAltitudeRef';

    protected $Name = 'GPSAltitudeRef';

    protected $FullName = 'XMP::exif';

    protected $GroupName = 'XMP-exif';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exif';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'GPS Altitude Ref';

    protected $local_g2 = 'Location';

    protected $Values = array(
        0 => array(
            'Id' => 0,
            'Label' => 'Above Sea Level',
        ),
        1 => array(
            'Id' => 1,
            'Label' => 'Below Sea Level',
        ),
    );

}
