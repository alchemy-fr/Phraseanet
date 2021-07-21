<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\PreviewIFD;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class XResolution extends AbstractTag
{

    protected $Id = 282;

    protected $Name = 'XResolution';

    protected $FullName = 'Nikon::PreviewIFD';

    protected $GroupName = 'PreviewIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'PreviewIFD';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'X Resolution';

    protected $flag_Permanent = true;

}
