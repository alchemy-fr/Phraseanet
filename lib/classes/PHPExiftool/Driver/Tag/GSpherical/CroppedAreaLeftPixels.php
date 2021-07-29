<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\GSpherical;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CroppedAreaLeftPixels extends AbstractTag
{

    protected $Id = 'CroppedAreaLeftPixels';

    protected $Name = 'CroppedAreaLeftPixels';

    protected $FullName = 'XMP::GSpherical';

    protected $GroupName = 'GSpherical';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GSpherical';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Cropped Area Left Pixels';

    protected $local_g1 = 'GSpherical';

    protected $flag_Avoid = true;

}
