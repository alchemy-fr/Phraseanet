<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPExifEX;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class StandardOutputSensitivity extends AbstractTag
{

    protected $Id = 'StandardOutputSensitivity';

    protected $Name = 'StandardOutputSensitivity';

    protected $FullName = 'XMP::exifEX';

    protected $GroupName = 'XMP-exifEX';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-exifEX';

    protected $g2 = 'Image';

    protected $Type = 'integer';

    protected $Writable = true;

    protected $Description = 'Standard Output Sensitivity';

}
