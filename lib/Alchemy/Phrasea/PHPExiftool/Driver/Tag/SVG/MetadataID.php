<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\SVG;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class MetadataID extends AbstractTag
{

    protected $Id = 'metadataId';

    protected $Name = 'MetadataID';

    protected $FullName = 'XMP::SVG';

    protected $GroupName = 'SVG';

    protected $g0 = 'SVG';

    protected $g1 = 'SVG';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Metadata ID';

}
