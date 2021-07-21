<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\SR2;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class SR2SubIFDKey extends AbstractTag
{

    protected $Id = 29217;

    protected $Name = 'SR2SubIFDKey';

    protected $FullName = 'Sony::SR2Private';

    protected $GroupName = 'SR2';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'SR2';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = false;

    protected $Description = 'SR2 Sub IFD Key';

    protected $flag_Permanent = true;

}
