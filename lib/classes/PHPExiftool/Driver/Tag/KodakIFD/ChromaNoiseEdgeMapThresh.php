<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\KodakIFD;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class ChromaNoiseEdgeMapThresh extends AbstractTag
{

    protected $Id = 3692;

    protected $Name = 'ChromaNoiseEdgeMapThresh';

    protected $FullName = 'Kodak::IFD';

    protected $GroupName = 'KodakIFD';

    protected $g0 = 'MakerNotes';

    protected $g1 = 'KodakIFD';

    protected $g2 = 'Image';

    protected $Type = 'int32u';

    protected $Writable = true;

    protected $Description = 'Chroma Noise Edge Map Thresh';

    protected $flag_Permanent = true;

}
