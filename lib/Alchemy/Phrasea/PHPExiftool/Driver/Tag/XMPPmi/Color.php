<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPPmi;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Color extends AbstractTag
{

    protected $Id = 'color';

    protected $Name = 'Color';

    protected $FullName = 'XMP::pmi';

    protected $GroupName = 'XMP-pmi';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-pmi';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Color';

    protected $flag_Avoid = true;

    protected $Values = array(
        'bw' => array(
            'Id' => 'bw',
            'Label' => 'BW',
        ),
        'color' => array(
            'Id' => 'color',
            'Label' => 'Color',
        ),
        'duotone' => array(
            'Id' => 'duotone',
            'Label' => 'Duotone',
        ),
        'quadtone' => array(
            'Id' => 'quadtone',
            'Label' => 'Quadtone',
        ),
        'sepia' => array(
            'Id' => 'sepia',
            'Label' => 'Sepia',
        ),
        'tritone' => array(
            'Id' => 'tritone',
            'Label' => 'Tritone',
        ),
    );

}
