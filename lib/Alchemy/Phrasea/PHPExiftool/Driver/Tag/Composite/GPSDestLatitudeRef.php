<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Composite;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class GPSDestLatitudeRef extends AbstractTag
{

    protected $Id = 'XMP::GPSDestLatitudeRef';

    protected $Name = 'GPSDestLatitudeRef';

    protected $FullName = 'Composite';

    protected $GroupName = 'Composite';

    protected $g0 = 'Composite';

    protected $g1 = 'Composite';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'GPS Dest Latitude Ref';

    protected $local_g2 = 'Location';

    protected $Values = array(
        'N' => array(
            'Id' => 'N',
            'Label' => 'North',
        ),
        'S' => array(
            'Id' => 'S',
            'Label' => 'South',
        ),
    );

}
