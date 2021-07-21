<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPCrd;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class MoireFilter extends AbstractTag
{

    protected $Id = 'MoireFilter';

    protected $Name = 'MoireFilter';

    protected $FullName = 'XMP::crd';

    protected $GroupName = 'XMP-crd';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-crd';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Moire Filter';

    protected $flag_Avoid = true;

    protected $Values = array(
        'Off' => array(
            'Id' => 'Off',
            'Label' => 'Off',
        ),
        'On' => array(
            'Id' => 'On',
            'Label' => 'On',
        ),
    );

}
