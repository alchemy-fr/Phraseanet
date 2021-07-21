<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Parrot;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class DroneQuaternion extends AbstractTag
{

    protected $Id = 28;

    protected $Name = 'DroneQuaternion';

    protected $FullName = 'mixed';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'mixed';

    protected $Type = 'int16s';

    protected $Writable = false;

    protected $Description = 'Drone Quaternion';

    protected $MaxLength = 4;

}
