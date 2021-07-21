<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\Opus;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class AudioChannels extends AbstractTag
{

    protected $Id = 1;

    protected $Name = 'AudioChannels';

    protected $FullName = 'Opus::Header';

    protected $GroupName = 'Opus';

    protected $g0 = 'Opus';

    protected $g1 = 'Opus';

    protected $g2 = 'Audio';

    protected $Type = 'int8u';

    protected $Writable = false;

    protected $Description = 'Audio Channels';

}
