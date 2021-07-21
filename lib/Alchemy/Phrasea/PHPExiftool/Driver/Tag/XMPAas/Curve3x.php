<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPAas;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Curve3x extends AbstractTag
{

    protected $Id = 'Curve3x';

    protected $Name = 'Curve3x';

    protected $FullName = 'XMP::aas';

    protected $GroupName = 'XMP-aas';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-aas';

    protected $g2 = 'Image';

    protected $Type = 'real';

    protected $Writable = true;

    protected $Description = 'Curve 3x';

}
