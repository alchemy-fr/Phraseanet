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
class GPSLatitudeRef extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'GPSLatitudeRef';

    protected $FullName = 'mixed';

    protected $GroupName = 'GPS';

    protected $g0 = 'mixed';

    protected $g1 = 'mixed';

    protected $g2 = 'mixed';

    protected $Type = 'mixed';

    protected $Writable = false;

    protected $Description = 'GPS Latitude Ref';

    protected $MaxLength = 2;

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

    protected $local_g1 = 'mixed';

    protected $local_g2 = 'mixed';

    protected $flag_Permanent = 'mixed';

}
