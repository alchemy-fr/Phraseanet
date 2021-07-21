<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\AC3;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AudioBitrate extends AbstractTag
{

    protected $Id = 'AudioBitrate';

    protected $Name = 'AudioBitrate';

    protected $FullName = 'M2TS::AC3';

    protected $GroupName = 'AC3';

    protected $g0 = 'M2TS';

    protected $g1 = 'AC3';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Audio Bitrate';

}
