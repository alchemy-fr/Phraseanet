<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\Keys;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CaptureMode extends AbstractTag
{

    protected $Id = 'com.apple.photos.captureMode';

    protected $Name = 'CaptureMode';

    protected $FullName = 'QuickTime::Keys';

    protected $GroupName = 'Keys';

    protected $g0 = 'QuickTime';

    protected $g1 = 'Keys';

    protected $g2 = 'Other';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Capture Mode';

}
