<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPIptcExt;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ImageRegionBoundaryUnit extends AbstractTag
{

    protected $Id = 'ImageRegionRegionBoundaryRbUnit';

    protected $Name = 'ImageRegionBoundaryUnit';

    protected $FullName = 'XMP::iptcExt';

    protected $GroupName = 'XMP-iptcExt';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-iptcExt';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Region Boundary Unit';

    protected $local_g2 = 'Image';

    protected $flag_List = true;

    protected $Values = array(
        'pixel' => array(
            'Id' => 'pixel',
            'Label' => 'Pixel',
        ),
        'relative' => array(
            'Id' => 'relative',
            'Label' => 'Relative',
        ),
    );

}
