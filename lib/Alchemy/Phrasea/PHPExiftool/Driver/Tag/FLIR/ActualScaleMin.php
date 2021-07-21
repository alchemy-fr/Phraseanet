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
class ActualScaleMin extends AbstractTag
{

    protected $Id = 692;

    protected $Name = 'ActualScaleMin';

    protected $FullName = 'FLIR::FPF';

    protected $GroupName = 'FLIR';

    protected $g0 = 'FLIR';

    protected $g1 = 'FLIR';

    protected $g2 = 'Image';

    protected $Type = 'float';

    protected $Writable = false;

    protected $Description = 'Actual Scale Min';

}
