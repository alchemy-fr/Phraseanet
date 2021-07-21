<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\FLIR;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AtmosphericTransBeta1 extends AbstractTag
{

    protected $Id = 120;

    protected $Name = 'AtmosphericTransBeta1';

    protected $FullName = 'FLIR::CameraInfo';

    protected $GroupName = 'FLIR';

    protected $g0 = 'APP1';

    protected $g1 = 'FLIR';

    protected $g2 = 'Camera';

    protected $Type = 'float';

    protected $Writable = false;

    protected $Description = 'Atmospheric Trans Beta 1';

}
