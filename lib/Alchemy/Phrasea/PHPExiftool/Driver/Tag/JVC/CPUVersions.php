<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\JVC;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class CPUVersions extends AbstractTag
{

    protected $Id = 2;

    protected $Name = 'CPUVersions';

    protected $FullName = 'JVC::Main';

    protected $GroupName = 'JVC';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'JVC';

    protected $g2 = 'Camera';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'CPU Versions';

    protected $flag_Permanent = true;

}
