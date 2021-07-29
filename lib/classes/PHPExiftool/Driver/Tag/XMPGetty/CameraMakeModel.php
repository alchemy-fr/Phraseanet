<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGetty;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CameraMakeModel extends AbstractTag
{

    protected $Id = 'CameraMakeModel';

    protected $Name = 'CameraMakeModel';

    protected $FullName = 'XMP::GettyImages';

    protected $GroupName = 'XMP-getty';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-getty';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Camera Make Model';

    protected $flag_Avoid = true;

}
