<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\ID3v24;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class PlaylistDelay extends AbstractTag
{

    protected $Id = 'TDLY';

    protected $Name = 'PlaylistDelay';

    protected $FullName = 'ID3::v2_4';

    protected $GroupName = 'ID3v2_4';

    protected $g0 = 'ID3';

    protected $g1 = 'ID3v2_4';

    protected $g2 = 'Audio';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Playlist Delay';

}
