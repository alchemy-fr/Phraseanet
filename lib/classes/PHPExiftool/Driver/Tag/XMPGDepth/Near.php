<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGDepth;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class Near extends AbstractTag
{

    protected $Id = 'Near';

    protected $Name = 'Near';

    protected $FullName = 'XMP::GDepth';

    protected $GroupName = 'XMP-GDepth';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GDepth';

    protected $g2 = 'Image';

    protected $Type = 'real';

    protected $Writable = true;

    protected $Description = 'Near';

    protected $flag_Avoid = true;

}
