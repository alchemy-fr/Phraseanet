<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\SR2DataIFD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ColorMode extends AbstractTag
{

    protected $Id = 30576;

    protected $Name = 'ColorMode';

    protected $FullName = 'Sony::SR2DataIFD';

    protected $GroupName = 'SR2DataIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'SR2DataIFD';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Color Mode';

    protected $flag_Permanent = true;

}
