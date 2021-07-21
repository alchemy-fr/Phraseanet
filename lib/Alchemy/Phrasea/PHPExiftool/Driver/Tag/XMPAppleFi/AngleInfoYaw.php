<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPAppleFi;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AngleInfoYaw extends AbstractTag
{

    protected $Id = 'AngleInfoYaw';

    protected $Name = 'AngleInfoYaw';

    protected $FullName = 'XMP::apple_fi';

    protected $GroupName = 'XMP-apple-fi';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-apple-fi';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Angle Info Yaw';

}
