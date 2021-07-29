<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGetty;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class GIFTFtpPriority extends AbstractTag
{

    protected $Id = 'GIFTFtpPriority';

    protected $Name = 'GIFTFtpPriority';

    protected $FullName = 'XMP::GettyImages';

    protected $GroupName = 'XMP-getty';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-getty';

    protected $g2 = 'Image';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'GIFT Ftp Priority';

}
