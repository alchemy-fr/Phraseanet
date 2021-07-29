<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Parrot;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class BlueBalance extends AbstractTag
{

    protected $Id = 58;

    protected $Name = 'BlueBalance';

    protected $FullName = 'Parrot::V3';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'Location';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Blue Balance';

    protected $local_g2 = 'Camera';

}
