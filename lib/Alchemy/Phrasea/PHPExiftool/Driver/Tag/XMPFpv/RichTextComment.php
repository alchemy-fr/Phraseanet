<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPFpv;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class RichTextComment extends AbstractTag
{

    protected $Id = 'RichTextComment';

    protected $Name = 'RichTextComment';

    protected $FullName = 'XMP::fpv';

    protected $GroupName = 'XMP-fpv';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-fpv';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Rich Text Comment';

}
