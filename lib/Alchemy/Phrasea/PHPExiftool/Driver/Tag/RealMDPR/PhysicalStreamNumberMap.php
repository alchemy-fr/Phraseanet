<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\RealMDPR;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PhysicalStreamNumberMap extends AbstractTag
{

    protected $Id = 19;

    protected $Name = 'PhysicalStreamNumberMap';

    protected $FullName = 'Real::MediaProps';

    protected $GroupName = 'Real-MDPR';

    protected $g0 = 'Real';

    protected $g1 = 'Real-MDPR';

    protected $g2 = 'Video';

    protected $Type = 'int16u';

    protected $Writable = false;

    protected $Description = 'Physical Stream Number Map';

}
