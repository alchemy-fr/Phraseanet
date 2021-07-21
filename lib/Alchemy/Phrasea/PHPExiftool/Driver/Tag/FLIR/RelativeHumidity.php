<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\FLIR;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RelativeHumidity extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'RelativeHumidity';

    protected $FullName = 'mixed';

    protected $GroupName = 'FLIR';

    protected $g0 = 'mixed';

    protected $g1 = 'FLIR';

    protected $g2 = 'mixed';

    protected $Type = 'float';

    protected $Writable = false;

    protected $Description = 'Relative Humidity';

    protected $flag_Permanent = 'mixed';

}
