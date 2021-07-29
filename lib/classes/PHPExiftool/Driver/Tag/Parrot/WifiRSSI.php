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
class WifiRSSI extends AbstractTag
{

    protected $Id = 'mixed';

    protected $Name = 'WifiRSSI';

    protected $FullName = 'mixed';

    protected $GroupName = 'Parrot';

    protected $g0 = 'Parrot';

    protected $g1 = 'Parrot';

    protected $g2 = 'mixed';

    protected $Type = 'int8s';

    protected $Writable = false;

    protected $Description = 'Wifi RSSI';

    protected $local_g2 = 'Device';

}
