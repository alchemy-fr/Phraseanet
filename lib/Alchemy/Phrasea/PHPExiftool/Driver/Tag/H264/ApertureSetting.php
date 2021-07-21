<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\H264;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ApertureSetting extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'ApertureSetting';

    protected $FullName = 'H264::Camera1';

    protected $GroupName = 'H264';

    protected $g0 = 'H264';

    protected $g1 = 'H264';

    protected $g2 = 'Camera';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Aperture Setting';

    protected $Values = array(
        254 => array(
            'Id' => 254,
            'Label' => 'Closed',
        ),
        255 => array(
            'Id' => 255,
            'Label' => 'Auto',
        ),
    );

}
