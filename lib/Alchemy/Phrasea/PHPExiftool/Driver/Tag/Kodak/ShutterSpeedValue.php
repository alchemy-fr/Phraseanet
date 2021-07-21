<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Kodak;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ShutterSpeedValue extends AbstractTag
{

    protected $Id = 'StSV';

    protected $Name = 'ShutterSpeedValue';

    protected $FullName = 'Kodak::Free';

    protected $GroupName = 'Kodak';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Kodak';

    protected $g2 = 'Video';

    protected $Type = 'int16s';

    protected $Writable = false;

    protected $Description = 'Shutter Speed Value';

    protected $flag_Permanent = true;

}
