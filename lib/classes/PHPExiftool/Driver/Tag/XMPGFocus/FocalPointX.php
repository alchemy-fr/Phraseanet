<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGFocus;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class FocalPointX extends AbstractTag
{

    protected $Id = 'FocalPointX';

    protected $Name = 'FocalPointX';

    protected $FullName = 'XMP::GFocus';

    protected $GroupName = 'XMP-GFocus';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GFocus';

    protected $g2 = 'Image';

    protected $Type = 'real';

    protected $Writable = true;

    protected $Description = 'Focal Point X';

}
