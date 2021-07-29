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
class ImageRegionBoundaryShape extends AbstractTag
{

    protected $Id = 'ImageRegionRegionBoundaryRbShape';

    protected $Name = 'ImageRegionBoundaryShape';

    protected $FullName = 'XMP::iptcExt';

    protected $GroupName = 'XMP-iptcExt';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-iptcExt';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Image Region Boundary Shape';

    protected $local_g2 = 'Image';

    protected $flag_List = true;

    protected $Values = array(
        'circle' => array(
            'Id' => 'circle',
            'Label' => 'Circle',
        ),
        'polygon' => array(
            'Id' => 'polygon',
            'Label' => 'Polygon',
        ),
        'rectangle' => array(
            'Id' => 'rectangle',
            'Label' => 'Rectangle',
        ),
    );

}
