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
class StitchingSoftware extends AbstractTag
{

    protected $Id = 'StitchingSoftware';

    protected $Name = 'StitchingSoftware';

    protected $FullName = 'XMP::GSpherical';

    protected $GroupName = 'GSpherical';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GSpherical';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Stitching Software';

    protected $local_g1 = 'GSpherical';

    protected $flag_Avoid = true;

}
