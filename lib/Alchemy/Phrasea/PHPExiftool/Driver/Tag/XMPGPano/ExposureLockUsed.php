<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPGPano;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class ExposureLockUsed extends AbstractTag
{

    protected $Id = 'ExposureLockUsed';

    protected $Name = 'ExposureLockUsed';

    protected $FullName = 'XMP::GPano';

    protected $GroupName = 'XMP-GPano';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GPano';

    protected $g2 = 'Image';

    protected $Type = 'boolean';

    protected $Writable = true;

    protected $Description = 'Exposure Lock Used';

}
