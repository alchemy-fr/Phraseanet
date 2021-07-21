<?php

/*
 * This file is part of the PHPExifTool package.
 *
 * (c) Alchemy <support@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\PHPExiftool\Driver\Tag\MediaJukebox;

use Alchemy\Phrasea\PHPExiftool\Driver\AbstractTag;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * @ExclusionPolicy("all")
 */
class Album extends AbstractTag
{

    protected $Id = 'Album';

    protected $Name = 'Album';

    protected $FullName = 'JPEG::MediaJukebox';

    protected $GroupName = 'MediaJukebox';

    protected $g0 = 'XML';

    protected $g1 = 'MediaJukebox';

    protected $g2 = 'Image';

    protected $Type = '?';

    protected $Writable = false;

    protected $Description = 'Album';

}
