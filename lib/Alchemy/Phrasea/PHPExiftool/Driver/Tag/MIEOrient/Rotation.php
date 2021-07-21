<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MIEOrient;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Rotation extends AbstractTag
{

    protected $Id = 'Rotation';

    protected $Name = 'Rotation';

    protected $FullName = 'MIE::Orient';

    protected $GroupName = 'MIE-Orient';

    protected $g0 = 'MIE';

    protected $g1 = 'MIE-Orient';

    protected $g2 = 'Camera';

    protected $Type = 'rational64s';

    protected $Writable = true;

    protected $Description = 'Rotation';

}
