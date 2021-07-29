<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPGAudio;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class AudioData extends AbstractTag
{

    protected $Id = 'Data';

    protected $Name = 'AudioData';

    protected $FullName = 'XMP::GAudio';

    protected $GroupName = 'XMP-GAudio';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GAudio';

    protected $g2 = 'Audio';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Audio Data';

}
