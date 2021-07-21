<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPGFocus;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class FocalPointY extends AbstractTag
{

    protected $Id = 'FocalPointY';

    protected $Name = 'FocalPointY';

    protected $FullName = 'XMP::GFocus';

    protected $GroupName = 'XMP-GFocus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GFocus';

    protected $g2 = 'Image';

    protected $Type = 'real';

    protected $Writable = true;

    protected $Description = 'Focal Point Y';

}
