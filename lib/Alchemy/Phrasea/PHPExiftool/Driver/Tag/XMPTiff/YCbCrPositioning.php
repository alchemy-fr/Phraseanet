<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPTiff;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class YCbCrPositioning extends AbstractTag
{

    protected $Id = 'YCbCrPositioning';

    protected $Name = 'YCbCrPositioning';

    protected $FullName = 'XMP::tiff';

    protected $GroupName = 'XMP-tiff';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-tiff';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Y Cb Cr Positioning';

    protected $Values = array(
        1 => array(
            'Id' => 1,
            'Label' => 'Centered',
        ),
        2 => array(
            'Id' => 2,
            'Label' => 'Co-sited',
        ),
    );

}
