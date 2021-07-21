<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPXmpTPg;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class SwatchColorantMode extends AbstractTag
{

    protected $Id = 'SwatchGroupsColorantsMode';

    protected $Name = 'SwatchColorantMode';

    protected $FullName = 'XMP::xmpTPg';

    protected $GroupName = 'XMP-xmpTPg';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmpTPg';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Swatch Colorant Mode';

    protected $flag_List = true;

    protected $Values = array(
        'CMYK' => array(
            'Id' => 'CMYK',
            'Label' => 'CMYK',
        ),
        'LAB' => array(
            'Id' => 'LAB',
            'Label' => 'Lab',
        ),
        'RGB' => array(
            'Id' => 'RGB',
            'Label' => 'RGB',
        ),
    );

}
