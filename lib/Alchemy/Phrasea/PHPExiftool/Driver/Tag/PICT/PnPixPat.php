<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\PICT;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PnPixPat extends AbstractTag
{

    protected $Id = 19;

    protected $Name = 'PnPixPat';

    protected $FullName = 'PICT::Main';

    protected $GroupName = 'PICT';

    protected $g0 = 'PICT';

    protected $g1 = 'PICT';

    protected $g2 = 'Other';

    protected $Type = 'PixPat';

    protected $Writable = false;

    protected $Description = 'Pen Pixel Pattern';

}
