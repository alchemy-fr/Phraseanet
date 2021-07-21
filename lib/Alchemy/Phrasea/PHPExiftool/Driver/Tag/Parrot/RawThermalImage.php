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
class RawThermalImage extends AbstractTag
{

    protected $Id = 'APP1';

    protected $Name = 'RawThermalImage';

    protected $FullName = 'JPEG::Main';

    protected $GroupName = 'Parrot';

    protected $g0 = 'JPEG';

    protected $g1 = 'JPEG';

    protected $g2 = 'Other';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Raw Thermal Image';

    protected $local_g0 = 'APP1';

    protected $local_g1 = 'Parrot';

    protected $local_g2 = 'Preview';

    protected $flag_Binary = true;

    protected $Index = 5;

}
