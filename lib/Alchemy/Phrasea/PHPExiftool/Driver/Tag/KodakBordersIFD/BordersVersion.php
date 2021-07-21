<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\KodakBordersIFD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class BordersVersion extends AbstractTag
{

    protected $Id = 0;

    protected $Name = 'BordersVersion';

    protected $FullName = 'Kodak::Borders';

    protected $GroupName = 'KodakBordersIFD';

    protected $g0 = 'Meta';

    protected $g1 = 'KodakBordersIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Borders Version';

}
