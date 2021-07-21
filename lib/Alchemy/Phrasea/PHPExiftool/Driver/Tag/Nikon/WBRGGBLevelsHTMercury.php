<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Nikon;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class WBRGGBLevelsHTMercury extends AbstractTag
{

    protected $Id = 216;

    protected $Name = 'WB_RGGBLevelsHTMercury';

    protected $FullName = 'Nikon::ColorBalanceC';

    protected $GroupName = 'Nikon';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'Nikon';

    protected $g2 = 'Camera';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'WB RGGB Levels HT Mercury';

    protected $flag_Permanent = true;

    protected $flag_Unsafe = true;

    protected $MaxLength = 4;

}
