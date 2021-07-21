<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\XMPGAudio;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AudioMimeType extends AbstractTag
{

    protected $Id = 'Mime';

    protected $Name = 'AudioMimeType';

    protected $FullName = 'XMP::GAudio';

    protected $GroupName = 'XMP-GAudio';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-GAudio';

    protected $g2 = 'Audio';

    protected $Type = 'string';

    protected $Writable = true;

    protected $Description = 'Audio Mime Type';

}
