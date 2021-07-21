<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPIcs;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class SubLabels3 extends AbstractTag
{

    protected $Id = 'TagStructureSubLabelsSubLabelsSubLabels';

    protected $Name = 'SubLabels3';

    protected $FullName = 'XMP::ics';

    protected $GroupName = 'XMP-ics';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-ics';

    protected $g2 = 'Image';

    protected $Type = 'struct';

    protected $Writable = true;

    protected $Description = 'Sub Labels 3';

    protected $flag_List = true;

    protected $flag_Bag = true;

}
