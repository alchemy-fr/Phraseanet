<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPGetty;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class MediaEventIdDate extends AbstractTag
{

    protected $Id = 'MediaEventIdDate';

    protected $Name = 'MediaEventIdDate';

    protected $FullName = 'XMP::GettyImages';

    protected $GroupName = 'XMP-getty';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-getty';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Media Event Id Date';

}
