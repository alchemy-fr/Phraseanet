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
class VisualColor extends AbstractTag
{

    protected $Id = 'VisualColour';

    protected $Name = 'VisualColor';

    protected $FullName = 'XMP::iptcExt';

    protected $GroupName = 'XMP-iptcExt';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-iptcExt';

    protected $g2 = 'Author';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Visual Color';

    protected $local_g2 = 'Video';

    protected $Values = array(
        'bw-monochrome' => array(
            'Id' => 'bw-monochrome',
            'Label' => 'Monochrome',
        ),
        'colour' => array(
            'Id' => 'colour',
            'Label' => 'Color',
        ),
    );

}
