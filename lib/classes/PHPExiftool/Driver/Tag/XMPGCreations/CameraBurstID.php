<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGCreations;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CameraBurstID extends AbstractTag
{

    protected $Id = 'CameraBurstID';

    protected $Name = 'CameraBurstID';

    protected $FullName = 'XMP::GCreations';

    protected $GroupName = 'XMP-GCreations';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GCreations';

    protected $g2 = 'Camera';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Camera Burst ID';

}
